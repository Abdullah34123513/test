const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

// Helper to run shell commands
const run = (cmd, cwd = process.cwd(), ignoreError = false) => {
    try {
        console.log(`\n> Running: ${cmd} in ${cwd}`);
        execSync(cmd, { stdio: 'inherit', cwd });
    } catch (e) {
        if (ignoreError) {
            console.log(`⚠️ Command failed but continuing: ${cmd}`);
        } else {
            console.error(`❌ Critical failure: ${cmd}`);
            process.exit(1);
        }
    }
};

const checkAndInstall = () => {
    console.log("\n🔍 Checking System Dependencies...");

    // 1. PHP & Extensions
    try {
        execSync('php -v');
        console.log("✅ PHP is installed.");
    } catch (e) {
        console.log("⚠️ PHP missing. Installing PHP 8.3 and extensions...");
        run('sudo apt update');
        run('sudo apt install -y software-properties-common');
        run('sudo add-apt-repository -y ppa:ondrej/php', process.cwd(), true);
        run('sudo apt update');
        run('sudo apt install -y php8.3 php8.3-fpm php8.3-xml php8.3-curl php8.3-mbstring php8.3-zip php8.3-mysql php8.3-sqlite3 php8.3-bcmath php8.3-intl php8.3-gd unzip');
    }

    // Always ensure extensions are present even if PHP exists
    console.log("🛠️ Ensuring required PHP extensions are present...");
    run('sudo apt install -y php8.3-xml php8.3-curl php8.3-mbstring php8.3-zip php8.3-mysql php8.3-sqlite3 php8.3-bcmath php8.3-intl php8.3-gd');

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

async function main() {
    console.log("\x1b[36m%s\x1b[0m", "Starting Dynamic VPS Provisioning & Deployment...");

    // Step 0: System Check
    checkAndInstall();

    // 0. Update Code
    console.log("\n⬇️ Pulling latest changes from Git...");
    run('git pull origin main');

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
    run('php artisan storage:link');

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
            // Check if ecosystem.config.js exists
            if (fs.existsSync('ecosystem.config.js')) {
                run('pm2 restart ecosystem.config.js --env production');
            } else {
                run('pm2 restart video-server || pm2 start video-server/server.js --name video-server');
            }
        } catch (e) {
            console.log("⚠️ PM2 command failed. Make sure PM2 is installed: npm install -g pm2");
        }
    }

    console.log("\n🔒 Setting Permissions...");
    run('chown -R www-data:www-data storage bootstrap/cache');
    run('chmod -R 775 storage bootstrap/cache');

    console.log("\n✨ VPS Deployment Finished! ✨");
    process.exit(0);
}

main();
