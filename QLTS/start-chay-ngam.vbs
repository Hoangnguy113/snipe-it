Set WshShell = CreateObject("WScript.Shell")
WshShell.Run "wsl -d Ubuntu -u root -- /mnt/d/DEV/QLTS/start_server.sh", 0, False
WScript.Sleep 3000
WshShell.Run "http://localhost:8080/"
