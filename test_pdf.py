import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('192.168.128.111', username='root', password='r00twidaD')

cmd = """docker exec simpul_backend php -r "echo file_get_contents('http://localhost:8000/api/v1/utilities/pdf', false, stream_context_create(['http' => ['method' => 'POST', 'header' => 'Content-Type: application/x-www-form-urlencoded', 'content' => http_build_query(['html' => '<h1>Test</h1>'])]]));" """

_, stdout, stderr = ssh.exec_command(cmd)

print("STDOUT:", stdout.read().decode('utf-8')[:1000])
print("STDERR:", stderr.read().decode('utf-8'))

ssh.close()
