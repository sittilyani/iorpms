<?php
// _pump_device_fields.php
// Shared form fields for pump_devices.php (add and edit forms)
// Variables ($label, $port, $pump_host, $is_reversed, $api_secret)
// are empty when used for the Add form and populated in JS for the Edit modal.
?>
<div class="form-group">
    <label>Label <span style="color:red;">*</span></label>
    <input type="text" name="label" placeholder="e.g. Pump A – Station 1" required>
</div>

<div class="form-group">
    <label>COM Port <span style="color:red;">*</span></label>
    <input type="text" name="port" placeholder="e.g. COM6" pattern="COM\d{1,3}" title="Must be COM followed by a number, e.g. COM6" required>
    <div class="hint">Serial port the pump is connected to on its host machine.</div>
</div>

<div class="form-group">
    <label>Pump Host / IP Address</label>
    <input type="text" name="pump_host" placeholder="localhost" value="localhost">
    <div class="hint">
        Use <strong>localhost</strong> if the pump is on <em>this server</em>.
        For a pharmacy-station PC, enter its LAN IP, e.g. <code>192.168.1.101</code>.
    </div>
</div>

<div class="form-group">
    <label>
        <input type="checkbox" name="is_reversed" value="1" style="width:auto; margin-right:8px;">
        Pump runs in <strong>reverse direction</strong>
    </label>
    <div class="hint">
        Tick this if the pump motor is physically mounted in reverse (pumps backward when given a forward command).
        The system will send a direction-invert command automatically before each dispense.
    </div>
</div>

<div class="form-group">
    <label>API Secret (for remote/client pumps)</label>
    <input type="text" name="api_secret" class="secret-field" placeholder="Leave blank for server-local pumps" autocomplete="off">
    <div class="hint">
        Required only when Pump Host is a remote IP. Must match the
        <code>PUMP_API_SECRET</code> constant in <code>pump/local_pump_api.php</code> on that client machine.
        Use a long random string (e.g. 32+ characters).
    </div>
</div>
