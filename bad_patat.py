"""
bad Patat
Throw it away, or convert it.

This is to help my DJI Mavic Air 2s and DJI Osmo Pocket H.264 files to work in DaVinci Linux, thanks to proprietry h264 foo.

This script processes (multi threads) a directory of video files, converting them to the DNxHD format into a mov container.
The script maintains the original resolution and frame rate of each video file. Whether or not this breaks standards, Id ont know
Converted files are saved in a subdirectory named 'transcoded_DNxHD' within the source directory.

Author: Marlon van der Linde
Date: 14 October 2024

Usage:
    python bad_patat.py <source_dir where your videos are>

Dependencies to install on your l00nix:
    - Python 3.x
    - ffmpeg (https://ffmpeg.org/download.html)
    - ffprobe (typically included with ffmpeg)

"""

import os
import sys
import subprocess
import datetime
from concurrent.futures import ThreadPoolExecutor, as_completed

# check if the file is a video. Naive, by extension
def is_video_file(filename):
    extensions = ('.mp4', '.mkv', '.avi', '.mov')
    return filename.lower().endswith(extensions)

# ffprobe the file for res/framerate etc
def get_video_info(filepath):
    cmd = [
        'ffprobe',
        '-v', 'error',
        '-select_streams', 'v:0',
        '-show_entries', 'stream=width,height,r_frame_rate,codec_name',
        '-show_entries', 'format=size',
        '-of', 'default=noprint_wrappers=1',
        filepath
    ]
    result = subprocess.run(cmd, capture_output=True, text=True, check=True)
    info = {}
    for line in result.stdout.splitlines():
        key, value = line.split('=')
        info[key] = value
    info['size'] = float(info['size']) / (1024 * 1024)  # Convert to MB
    return info

# conversion command built and called here
def convert_video(input_path, output_path, width, height):
    try:
        cmd = [
            'ffmpeg',
            '-i', input_path,
            '-vf', f'scale={width}:{height}',
            '-c:v', 'dnxhd',
            '-profile:v', 'dnxhr_hq',
            '-pix_fmt', 'yuv422p',
            '-c:a', 'pcm_s16le',
            '-f', 'mov',
            output_path
        ]
        subprocess.run(cmd, check=True, capture_output=True)
        return True, ""
    except subprocess.CalledProcessError as e:
        return False, e.stderr.decode()

# get output to console, so you don't have to listen to the cpu fan to know if something is happening
def log_conversion_details(input_info, output_info, success, error):
    timestamp = datetime.datetime.now().isoformat()
    status = "Success" if success else "Failure"
    
    print(f"{timestamp} - Conversion {status}")
    print(f"Input: {input_info}")
    if success:
        print(f"Output: {output_info}")
    if not success:
        print(f"Error: {error}")	# no refunds, makes backups. Own risk

# each thread runs one of these, to process, check info, etc
def process_single_video(file, source_dir, output_dir):
    input_path = os.path.join(source_dir, file)
    output_path = os.path.join(output_dir, f"transcoded_{os.path.splitext(file)[0]}.mov")
    
    input_info = get_video_info(input_path)
    width = input_info.get('width')
    height = input_info.get('height')
    
    success, error = convert_video(input_path, output_path, width, height)
    output_info = get_video_info(output_path) if success else None
    
    log_conversion_details(input_info, output_info, success, error)

# iterate and thread up
def process_videos(source_dir):
    output_dir = os.path.join(source_dir, 'transcoded_DNxHD')
    os.makedirs(output_dir, exist_ok=True)
    
    video_files = [file for file in os.listdir(source_dir) if is_video_file(file)]
    
    with ThreadPoolExecutor(max_workers=4) as executor:
        futures = [executor.submit(process_single_video, file, source_dir, output_dir) for file in video_files]
        
        for future in as_completed(futures):
            future.result()  # This will raise any exceptions caught during processing

if __name__ == "__main__":
    if len(sys.argv) != 2:
        print("Usage: python3 bad_patat.py <source_dir>")
        sys.exit(1)
    
    source_directory = sys.argv[1]
    if not os.path.isdir(source_directory):
        print("The specified source directory does not exist.")
        sys.exit(1)
    
    process_videos(source_directory)

