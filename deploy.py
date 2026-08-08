import paramiko
from scp import SCPClient
import sys

def create_ssh_client(server, port, user, password):
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    try:
        client.connect(server, port, user, password, timeout=10)
        return client
    except Exception as e:
        print(f"Failed to connect to {server}: {e}")
        sys.exit(1)

def run_command(client, command):
    print(f"Executing: {command}")
    stdin, stdout, stderr = client.exec_command(command)
    exit_status = stdout.channel.recv_exit_status()
    output = stdout.read().decode('utf-8')
    error = stderr.read().decode('utf-8')
    
    if exit_status != 0:
        print(f"Command failed with exit status {exit_status}")
        if error:
            print(f"Error: {error}")
    
    if output:
        print(f"Output: {output}")
        
    return exit_status, output, error

import argparse

def main():
    parser = argparse.ArgumentParser(description="Deploy Simpul DFIR")
    parser.add_argument("host", help="Target server IP")
    parser.add_argument("user", help="SSH username")
    parser.add_argument("password", help="SSH password")
    args = parser.parse_args()

    host = args.host
    user = args.user
    password = args.password
    port = 22
    
    print(f"Connecting to {host}...")
    ssh = create_ssh_client(host, port, user, password)
    print("Connected successfully!")
    
    print("Setting up directory structure on server...")
    run_command(ssh, "mkdir -p /opt/simpul-dfir/backend")
    run_command(ssh, "mkdir -p /opt/simpul-dfir/frontend")
    run_command(ssh, "mkdir -p /opt/simpul-dfir/agent")
    
    print("Uploading files via SCP...")
    try:
        with SCPClient(ssh.get_transport()) as scp:
            print("Uploading frontend...")
            scp.put("d:/Simpul-DFIR/index.html", remote_path="/opt/simpul-dfir/frontend/")
            
            print("Uploading backend scaffold...")
            scp.put("d:/Simpul-DFIR/scaffold.py", remote_path="/opt/simpul-dfir/backend/")
            
            print("Uploading agent scaffold...")
            scp.put("d:/Simpul-DFIR/scaffold_go.py", remote_path="/opt/simpul-dfir/agent/")
    except Exception as e:
        print(f"SCP Error: {e}")
        ssh.close()
        sys.exit(1)
        
    print("Running scaffold scripts on server...")
    run_command(ssh, "cd /opt/simpul-dfir/backend && python3 scaffold.py")
    run_command(ssh, "cd /opt/simpul-dfir/agent && python3 scaffold_go.py")
    
    print("Installing backend dependencies on server (using venv)...")
    run_command(ssh, "apt-get update && apt-get install -y python3-pip python3-venv")
    run_command(ssh, "cd /opt/simpul-dfir/backend && python3 -m venv venv && ./venv/bin/pip install -r requirements.txt")
    
    print("Starting FastAPI backend on server...")
    # Using nohup to run it in the background
    run_command(ssh, "cd /opt/simpul-dfir/backend && nohup ./venv/bin/uvicorn app.main:app --host 0.0.0.0 --port 8000 > backend.log 2>&1 &")
    
    print("Verifying backend is running...")
    run_command(ssh, "ps aux | grep uvicorn")
    
    ssh.close()
    print("Deployment completed!")

if __name__ == "__main__":
    main()
