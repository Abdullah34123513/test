const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

// Helper to run shell commands
const run = (cmd, cwd = process.cwd()) => {
    try {
        console.log(`\n> Running: ${cmd} in ${cwd}`);
        execSync(cmd, { stdio: 'inherit', cwd });
    } catch (e) {
        console.error(`Command failed: ${cmd}`);
        process.exit(1);
    }
};

async function main() {
    console.log("\x1b[36m%s\x1b[0m", "Starting VPS Deployment...");

    // 0. Update Code
    console.log("\n⬇️ Pulling latest changes from Git...");
    run('git pull origin main');

    // 1. Laravel Production Setup
    console.log("\n📦 Installing Composer Dependencies...");
    run('composer install --optimize-autoloader --no-dev');

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
