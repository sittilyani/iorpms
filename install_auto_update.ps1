# Called by install_auto_update.bat (elevated). Registers the "EasyFlow Auto Update" task:
#   - at start-up (first boot of the day), 3 min after boot so the network is up, then every 4 hours while the PC stays on
#   - every day at 08:00 local time; if the PC was off at 08:00 it runs as soon as it is next available
param([Parameter(Mandatory)][string]$Script)

$name = 'EasyFlow Auto Update'

$boot = New-ScheduledTaskTrigger -AtStartup
$boot.Delay = 'PT3M'
$boot.Repetition = (New-ScheduledTaskTrigger -Once -At '00:00' -RepetitionInterval (New-TimeSpan -Hours 4)).Repetition

$morning = New-ScheduledTaskTrigger -Daily -At '08:00'

$action   = New-ScheduledTaskAction -Execute $Script -WorkingDirectory (Split-Path $Script)
$settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries `
            -MultipleInstances IgnoreNew -ExecutionTimeLimit (New-TimeSpan -Hours 1)
$principal = New-ScheduledTaskPrincipal -UserId 'SYSTEM' -LogonType ServiceAccount -RunLevel Highest

Register-ScheduledTask -TaskName $name -Action $action -Trigger @($boot, $morning) -Settings $settings -Principal $principal -Force | Out-Null
Write-Host "Task '$name' registered."
