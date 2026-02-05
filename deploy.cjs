const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

// Helper to run shell commands
const run = (cmd, cwd = process.cwd(), ignoreError = false) => {
    try {
        console.log(`\n> Running: ${cmd} in ${cwd}`);
        // Allow composer as root automatically
        const env = { ...process.env, COMPOSER_ALLOW_SUPERUSER: '1' };
        execSync(cmd, { stdio: 'inherit', cwd, env });
    } catch (e) {
        if (ignoreError) {
            console.log(`⚠️ Command failed but continuing: ${cmd}`);
        } else {
            console.error(`❌ Critical failure: ${cmd}`);
            process.exit(1);
        }
    }
};

const getPhpVersion = () => {
    try {
        return execSync("php -r 'echo PHP_MAJOR_VERSION.\".\".PHP_MINOR_VERSION;'").toString().trim();
    } catch (e) {
        return '8.3'; // Default to 8.3 if not installed
    }
};

let phpVersion = getPhpVersion();

const checkAndInstall = () => {
    console.log("\n🔍 Checking System Dependencies...");

    // 1. PHP & Extensions
    try {
        execSync('php -v');
        console.log(`✅ PHP ${phpVersion} is installed.`);
    } catch (e) {
        console.log("⚠️ PHP missing. Installing PHP 8.3 and extensions...");
        run('sudo apt update');
        run('sudo apt install -y software-properties-common');
        run('sudo add-apt-repository -y ppa:ondrej/php', process.cwd(), true);
        run('sudo apt update');
        run('sudo apt install -y php8.3 php8.3-fpm php8.3-xml php8.3-curl php8.3-mbstring php8.3-zip php8.3-mysql php8.3-sqlite3 php8.3-bcmath php8.3-intl php8.3-gd unzip');
        phpVersion = '8.3';
    }

    // Always ensure extensions and FPM are present for the current version
    console.log(`🛠️ Ensuring required PHP ${phpVersion} extensions and FPM are present...`);
    run(`sudo apt install -y php${phpVersion}-fpm php${phpVersion}-xml php${phpVersion}-curl php${phpVersion}-mbstring php${phpVersion}-zip php${phpVersion}-mysql php${phpVersion}-sqlite3 php${phpVersion}-bcmath php${phpVersion}-intl php${phpVersion}-gd`);

    // 2. Node.js & NPM
    try {
        execSync('node -v');
        console.log("✅ Node.js is installed.");
    } catch (e) {
        console.log("⚠️ Node.js missing. Installing...");
        run('curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -');
        run('sudo apt install -y nodejs');
    }

    // 3. Nginx
    try {
        execSync('nginx -v');
        console.log("✅ Nginx is installed.");
    } catch (e) {
        console.log("⚠️ Nginx missing. Installing...");
        run('sudo apt install -y nginx');
    }

    // 4. PM2
    try {
        execSync('pm2 -v');
        console.log("✅ PM2 is installed.");
    } catch (e) {
        console.log("⚠️ PM2 missing. Installing globally...");
        run('sudo npm install -g pm2');
    }

    // 5. Composer
    try {
        execSync('composer -v');
        console.log("✅ Composer is installed.");
    } catch (e) {
        console.log("⚠️ Composer missing. Installing...");
        run('curl -sS https://getcomposer.org/installer | php');
        run('sudo mv composer.phar /usr/local/bin/composer');
    }
};

const setupNginx = () => {
    console.log("\n🌐 Configuring Nginx Site...");
    const projectRoot = process.cwd();
    const domain = "localhost"; // You can change this or detect IP

    // Detect public IP
    let ip = "127.0.0.1";
    try {
        ip = execSync('curl -s http://icanhazip.com').toString().trim();
        console.log(`Detected public IP: ${ip}`);
    } catch (e) {
        console.log("Could not detect public IP, using localhost.");
    }

    const nginxConfig = `server {
    listen 80;
    server_name ${ip} ${domain};
    root ${projectRoot}/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Proxy for Node.js Signaling Server (Socket.io)
    location /socket.io/ {
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header Host $host;

        proxy_pass http://127.0.0.1:3000;

        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \\.php$ {
        fastcgi_pass unix:/var/run/php/php${phpVersion}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\\.(?!well-known).* {
        deny all;
    }
}
`;

    const configPath = '/etc/nginx/sites-available/test-project';
    const enabledPath = '/etc/nginx/sites-enabled/test-project';

    try {
        fs.writeFileSync('nginx_tmp', nginxConfig);
        run(`sudo mv nginx_tmp ${configPath}`);
        run(`sudo ln -sf ${configPath} ${enabledPath}`);
        run('sudo rm -f /etc/nginx/sites-enabled/default', process.cwd(), true);
        run('sudo nginx -t');
        run('sudo systemctl restart nginx');

        // Ensure PHP-FPM is running and restarted for the correct version
        run(`sudo systemctl enable php${phpVersion}-fpm`, process.cwd(), true);
        run(`sudo systemctl restart php${phpVersion}-fpm`);

        console.log(`✅ Nginx and PHP${phpVersion}-FPM configured and restarted.`);
    } catch (e) {
        console.error("❌ Failed to configure Nginx: " + e.message);
    }
};

async function main() {
    console.log("\x1b[36m%s\x1b[0m", "Starting Dynamic VPS Provisioning & Deployment...");

    // Step 0: System Check
    checkAndInstall();

    // Step 0.1: Nginx Check/Config
    setupNginx();

    // 0. Update Code
    console.log("\n⬇️ Pulling latest changes from Git...");
    run('git pull origin main');

    // 0.5 .env Setup
    console.log("\n📄 Checking .env file...");
    if (!fs.existsSync('.env')) {
        console.log("Creating .env from .env.example...");
        run('cp .env.example .env');
        run('php artisan key:generate');
        // Update .env for SQLite
        let envContent = fs.readFileSync('.env', 'utf8');
        envContent = envContent.replace(/DB_CONNECTION=.*/g, 'DB_CONNECTION=sqlite');
        envContent = envContent.replace(/DB_DATABASE=.*/g, 'DB_DATABASE=' + path.join(process.cwd(), 'database', 'database.sqlite'));
        fs.writeFileSync('.env', envContent);
        console.log("✅ .env created and configured for SQLite.");
    }

    // 1. Laravel Production Setup
    console.log("\n📦 Installing Composer Dependencies...");
    run('composer install --optimize-autoloader --no-dev');

    // 1.1 SQLite Initialization
    console.log("\n🗄️ Checking SQLite Database...");
    const dbPath = path.join(process.cwd(), 'database', 'database.sqlite');
    if (!fs.existsSync(dbPath)) {
        console.log("Creating database.sqlite file...");
        run(`touch ${dbPath}`);
        // Ensure the directory itself has correct permissions
        run('chmod -R 775 database');
        run('chown -R www-data:www-data database');
    }

    console.log("\n🗄️ Running Database Migrations...");
    run('php artisan migrate --force');

    console.log("\n🌱 Seeding Admin User...");
    run('php artisan db:seed --class=AdminUserSeeder --force');

    console.log("\n🔗 Linking Storage...");
    run('php artisan storage:link', process.cwd(), true); // Ignore error if link exists

    console.log("\n🚀 Optimizing Caches...");
    run('php artisan optimize');

    // 2. Video Server Setup
    console.log("\n🎥 Setting up Video Signaling Server...");
    const videoServerDir = path.join(process.cwd(), 'video-server');

    if (fs.existsSync(videoServerDir)) {
        console.log("Installing Node.js dependencies...");
        run('npm install', videoServerDir);

        console.log("Restarting Signaling Server via PM2...");
        try {
            // Check if ecosystem.config.cjs exists
            if (fs.existsSync('ecosystem.config.cjs')) {
                run('pm2 restart ecosystem.config.cjs --env production');
            } else {
                run('pm2 restart video-server || pm2 start video-server/server.js --name video-server');
            }
        } catch (e) {
            console.log("⚠️ PM2 command failed. Make sure PM2 is installed: npm install -g pm2");
        }
    }

    console.log("\n🔒 Setting Permissions...");
    // Ensure all directories are enterable/readable by Nginx
    run('chmod -R 755 .');
    // Specific write permissions for Laravel
    run('chown -R www-data:www-data storage bootstrap/cache database');
    run('chmod -R 775 storage bootstrap/cache database');
    // Ensure logs directory exists and is writable
    if (!fs.existsSync('storage/logs')) {
        run('mkdir -p storage/logs');
    }
    run('chown -R www-data:www-data storage/logs');
    run('chmod -R 775 storage/logs');

    console.log("\n✨ VPS Deployment Finished! ✨");
    process.exit(0);
}

main();
