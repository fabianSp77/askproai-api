# Notification Sounds

This directory contains notification sounds for the call dashboard.

## Files
- `notification.wav` - Generated notification sound (WAV format)
- `notification.mp3` - MP3 version (requires ffmpeg)
- `notification.ogg` - OGG version (requires ffmpeg)

## Generating Sounds

To generate the MP3 and OGG versions from the WAV file:

```bash
# Install ffmpeg if not available
apt-get install ffmpeg

# Convert WAV to MP3
ffmpeg -i notification.wav -acodec mp3 -ab 128k notification.mp3

# Convert WAV to OGG
ffmpeg -i notification.wav -acodec libvorbis -ab 128k notification.ogg
```

## Custom Sounds

You can replace these files with any notification sound you prefer. 
Just ensure the files are named:
- notification.mp3
- notification.ogg

The dashboard will use whichever format the browser supports.