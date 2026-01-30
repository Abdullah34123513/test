const fs = require('fs');
const path = require('path');
const os = require('os');
const ffmpeg = require('fluent-ffmpeg');
const ffmpegPath = require('ffmpeg-static');

// Set ffmpeg path
ffmpeg.setFfmpegPath(ffmpegPath);

// Get Stream ID from arguments
const streamId = process.argv[2];

if (!streamId) {
    console.error('Error: Stream ID is required');
    process.exit(1);
}

// Define paths
const projectRoot = __dirname;
const storagePath = path.join(projectRoot, 'storage/app/public/live_streams', streamId);
const outputPath = path.join(storagePath, 'full_recording.m4a');

console.log(`Processing Stream: ${streamId}`);
console.log(`Searching in: ${storagePath}`);

if (!fs.existsSync(storagePath)) {
    console.error('Error: Stream directory not found');
    process.exit(1);
}

// Find all chunk files
const files = fs.readdirSync(storagePath)
    .filter(file => file.startsWith('chunk_') && file.endsWith('.m4a'))
    .sort((a, b) => {
        // Sort by sequence number extracted from filename chunk_{seq}_{timestamp}.m4a
        const seqA = parseInt(a.split('_')[1]);
        const seqB = parseInt(b.split('_')[1]);
        return seqA - seqB;
    });

if (files.length === 0) {
    console.error('Error: No chunks found');
    process.exit(1);
}

console.log(`Found ${files.length} chunks. Merging...`);

// Create FFmpeg command
const command = ffmpeg();

files.forEach(file => {
    const filePath = path.join(storagePath, file);
    command.input(filePath);
});

// Execute merge
command
    .on('error', (err) => {
        console.error('An error occurred:', err.message);
        process.exit(1);
    })
    .on('end', () => {
        console.log('Merge finished successfully!');
        console.log(`Output: ${outputPath}`);
        process.exit(0);
    })
    .mergeToFile(outputPath, path.join(os.tmpdir(), 'ffmpeg_merge_' + Date.now()));
