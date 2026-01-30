const { execSync } = require('child_process');
const fs = require('fs');
const readline = require('readline');

const rl = readline.createInterface({
    input: process.stdin,
    output: process.stdout
});

// Helper to run shell commands
const run = (cmd) => {
    try {
        console.log(`\n> Running: ${cmd}`);
        execSync(cmd, { stdio: 'inherit' });
    } catch (e) {
        console.error(`Command failed: ${cmd}`);
        // We generally want to continue even if non-critical error, 
        // but for critical steps checking exit code might be needed.
    }
};

const ask = (question) => new Promise(resolve => rl.question(question, resolve));

async function main() {
    console.log("\x1b[36m%s\x1b[0m", "Starting Automatic Deployment for Hostinger...");

    // 1. Environment Setup
    let envContent = '';
    const envPath = '.env';
    const examplePath = '.env.example';

    if (fs.existsSync(envPath)) {
        console.log("✅ .env file exists.");
        envContent = fs.readFileSync(envPath, 'utf8');
    } else {
        console.log("⚠️ .env file not found. Creating from example...");
        if (fs.existsSync(examplePath)) {
            fs.copyFileSync(examplePath, envPath);
            envContent = fs.readFileSync(envPath, 'utf8');
        } else {
            console.error("❌ Error: .env.example missing. Cannot setup environment.");
            process.exit(1);
        }
    }

    // Check if configuration is needed (simplistic check for default Laravel values)
    // If DB_DATABASE is 'laravel' or empty (followed by newline).
    if (envContent.includes('DB_DATABASE=laravel') || envContent.match(/DB_DATABASE=\r?\n/)) {
        console.log("\nPlease provide your Database credentials (from Hostinger Dashboard):");

        const dbHost = await ask("DB Host (default: 127.0.0.1): ") || '127.0.0.1';
        const dbName = await ask("DB Database Name: ");
        const dbUser = await ask("DB Username: ");
        const dbPass = await ask("DB Password: ");
        const appUrl = await ask("App URL (e.g., https://your-domain.com): ");

        let newEnv = envContent
            .replace(/DB_HOST=127.0.0.1/, `DB_HOST=${dbHost}`)
            .replace(/DB_DATABASE=laravel/, `DB_DATABASE=${dbName}`)
            .replace(/DB_USERNAME=root/, `DB_USERNAME=${dbUser}`)
            .replace(/DB_PASSWORD=/, `DB_PASSWORD=${dbPass}`)
            .replace(/APP_URL=http:\/\/localhost/, `APP_URL=${appUrl}`)
            .replace(/APP_ENV=local/, 'APP_ENV=production')
            .replace(/APP_DEBUG=true/, 'APP_DEBUG=false');

        fs.writeFileSync(envPath, newEnv);
        console.log("✅ .env updated.");

        console.log("Generating App Key...");
        run('php artisan key:generate');
    } else {
        console.log("✅ Using existing .env configuration.");
    }

    // 2. Install Dependencies
    console.log("\n📦 Installing Composer Dependencies...");
    run('composer install --optimize-autoloader --no-dev');

    // 3. Migrations & Seeding
    console.log("\n🗄️ Running Database Migrations...");
    run('php artisan migrate --force');

    console.log("\n🌱 Seeding Admin User...");
    run('php artisan db:seed --class=AdminUserSeeder --force');

    // 4. Storage Link (The Fix)
    console.log("\n🔗 Linking Storage...");
    if (fs.existsSync('public/storage')) {
        console.log("✅ Link public/storage already exists.");
    } else {
        // Use ln -s directly to bypass PHP exec restriction
        try {
            // Check if we are in root or public_html
            // Assuming we are in project root
            if (fs.existsSync('public')) {
                // Change to public dir to make relative linking easy
                const cwd = process.cwd();
                process.chdir('public');
                run('ln -s ../storage/app/public storage');
                process.chdir(cwd);
                console.log("✅ Storage linked successfully via manual command.");
            } else {
                console.error("❌ 'public' directory not found. Cannot link storage.");
            }
        } catch (e) {
            console.error("❌ Failed to create symlink: " + e.message);
        }
    }

    // 5. Caching for Production
    console.log("\n🚀 Optimizing Caches...");
    run('php artisan config:cache');
    run('php artisan route:cache');
    run('php artisan view:cache');

    // 6. Permissions
    console.log("\n🔒 Setting Permissions...");
    run('chmod -R 775 storage bootstrap/cache');

    // 7. .htaccess setup for redirection
    console.log("\n🌐 Checking .htaccess...");
    const htaccessPath = '.htaccess';
    const htaccessContent = `<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>`;

    if (!fs.existsSync(htaccessPath)) {
        console.log("Creating root .htaccess to redirect to /public...");
        fs.writeFileSync(htaccessPath, htaccessContent);
        console.log("✅ .htaccess created.");
    } else {
        console.log("✅ .htaccess already exists.");
    }

    console.log("\n✨ Deployment Script Finished! ✨");
    rl.close();
}

main();
