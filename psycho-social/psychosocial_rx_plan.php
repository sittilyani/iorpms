<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Psychosocial_rx_plan</title>
</head>
<body>
    <div class="content-main">
        <h2>MAT PSYCHOCIAL TREATMENT PLAN - FORM 2B</h2>
        <form action="POST">
            <div class="form-group">
                <label for="clientName">ClienName</label>
                <input type="text" name="clientName" name="mat_id" class="readonly-input" readonly>
            </div>
            <div class="form-group">
                <label for="mat_id">MAT ID</label>
                <input type="text" name="mat_id" class="readonly-input" readonly>
            </div>
            <div class="form-group">
                <label for="visitDate">Visit Date</label>
                <input type="date" name="visitDate" class="readonly-input" readonly>
            </div>
            <div class="form-group">
                <label for="rx_stage">Stage of treatment</label>
                <input type="text" name="rx_stage">
            </div>
            <div class="form-group">
                <label for="domain_problems">Noted domain problems</label>
                <select name="domain_problems" id="domain_problems"></select>
                <option value=""></option> <!--get options from table 'psychiatric_rx_problems'-->
            </div>
            <div class="form-group">
                <label for="therapist_name">Counsellor Name</label>
                <select name="therapist_name" id="therapist_name" class="readonly-input" readonly></select>
                <option value=""></option> <!--get options from table 'psychosocial-outcomes'-->
            </div>
            <div class="form-group">
                <label for="next_appointment">Visit Date</label>
                <input type="date" name="next_appointment">
            </div>
        </form>
    </div>
</body>
</html>