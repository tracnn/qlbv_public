# Cấu hình
$ProjectDir = $PSScriptRoot
$UpdateBat = Join-Path $ProjectDir "update.bat"
$LogFile = Join-Path $ProjectDir "auto-update.log"

function Write-Log {
    param($Message)
    $TimeStamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $LogMessage = "[$TimeStamp] $Message"
    Add-Content -Path $LogFile -Value $LogMessage
    Write-Host $LogMessage
}

try {
    Set-Location $ProjectDir
    
    # 1. Kiểm tra kết nối mạng
    if (-not (Test-Connection -ComputerName github.com -Count 1 -Quiet)) {
        Write-Log "Khong co ket noi den github.com. Bo qua kiem tra update."
        return
    }

    # 2. Lay hash commit moi nhat tu remote (khong pull)
    # Su dung git ls-remote de khong lam thay doi state cua local repo
    $RemoteInfo = git ls-remote origin main
    if ($null -eq $RemoteInfo) {
        Write-Log "Khong the lay thong tin tu GitHub remote."
        return
    }
    $RemoteHash = $RemoteInfo.Split("`t")[0]
    
    # 3. Lay hash commit hien tai o local
    $LocalHash = git rev-parse HEAD

    if ($RemoteHash -ne $LocalHash) {
        Write-Log "Phien ban moi: $RemoteHash (Hien tai: $LocalHash). Bat dau chay update.bat..."
        
        # Thuc thi file batch cap nhat
        # Start-Process cho phep chay trong terminal hien tai va doi cho den khi ket thuc
        $process = Start-Process -FilePath $UpdateBat -Wait -NoNewWindow -PassThru
        
        if ($process.ExitCode -eq 0) {
            Write-Log "Cap nhat thanh cong!"
        } else {
            Write-Log "Loi khi chay update.bat. Ma loi: $($process.ExitCode)"
        }
    } else {
        # Silent khi khong co update de tranh spam log
        # Write-Log "Ung dung da o phien ban moi nhat."
    }
} catch {
    Write-Log "Da xay ra loi: $($_.Exception.Message)"
}
