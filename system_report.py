import subprocess
import random

def get_uptime_and_load():
    return subprocess.getoutput("uptime")

def get_disk_usage():
    return subprocess.getoutput("df -h | grep -E '^Filesystem|/|/mnt'")

def get_directory_usage():
    return subprocess.getoutput("du -sh /mnt/rumen/backups/users/ /mnt/rumen/backups/databases/ /mnt/rumen/* 2>/dev/null | grep -E '/mnt/rumen/backups/users/|/mnt/rumen/backups/databases/|/mnt/rumen/'")

def get_inode_usage():
    return subprocess.getoutput("df -i")

def get_top_processes():
    return subprocess.getoutput("ps -eo pid,ppid,cmd,%mem,%cpu --sort=-%mem | head")

def get_logged_in_users():
    return subprocess.getoutput("who")

def get_non_system_users():
    return subprocess.getoutput("awk -F: '$3 >= 1000 {print $1}' /etc/passwd")

def get_linux_quote():
    try:
        return subprocess.getoutput("/usr/games/fortune linuxcookie linux bofh-excuses pratchett paradoxum anarchism")
    except Exception as e:
        return "Error digesting cookie: " + str(e)

def send_email(subject, body):
    recipient = "yourname@whatever" # recipient email
    sender = "root@your-domain"  # sender email

    message = f"""From: {sender}
To: {recipient}
Subject: {subject}
Content-Type: text/html

{body}
"""

    process = subprocess.Popen(["/usr/sbin/sendmail", "-t", "-oi"], stdin=subprocess.PIPE)
    process.communicate(message.encode("utf-8"))
def main():
    report_html = f"""
    <html>
    <body>
    <h2>Daily System Report</h2>
    <h3>Uptime and Load</h3>
    <pre>{get_uptime_and_load()}</pre>
    <h3>Disk Usage</h3>
    <pre>{get_disk_usage()}</pre>
    <h3>Directory Usage</h3>
    <pre>{get_directory_usage()}</pre>
    <h3>Inode Usage</h3>
    <pre>{get_inode_usage()}</pre>
    <h3>Top Processes</h3>
    <pre>{get_top_processes()}</pre>
    <h3>Logged In Users</h3>
    <pre>{get_logged_in_users()}</pre>
    <h3>Non-System Users</h3>
    <pre>{get_non_system_users()}</pre>
    <h3>Linux Quote</h3>
    <blockquote>{get_linux_quote()}</blockquote>
    </body>
    </html>
    """
    send_email("Linux :: The Daily Server", report_html)

if __name__ == "__main__":
    main()