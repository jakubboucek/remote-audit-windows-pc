"    32bit..."
Get-ItemProperty HKLM:\Software\Microsoft\Windows\CurrentVersion\Uninstall\* | Export-CliXml -Path "%1"
If( Test-Path "HKLM:\Software\Wow6432Node\Microsoft\Windows\CurrentVersion\Uninstall\" ) {
	"    64bit..."
	Get-ItemProperty HKLM:\Software\Wow6432Node\Microsoft\Windows\CurrentVersion\Uninstall\* | Export-CliXml -Path "%2"
}
Else {
	"    64bit... (skipped on 32bit platform)"
}