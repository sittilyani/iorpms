<?php
// includes/languages.php
// EasyFlow-L – Multilingual support (English / French)
// To add a language: duplicate the 'en' block, change the key, and translate all values.

$languages = [
    // =====================================================================
    // ENGLISH
    // =====================================================================
    'en' => [
        // ----- Global / Auth -----
        'login'             => 'Login',
        'usernname'         => 'User Name',
        'password'          => 'Password',
        'system_name'       => 'Patient Management System',
        'about_us'          => 'About Us',
        'welcome'           => 'Welcome',
        'logout'            => 'Logout',
        'rolle'             => 'Role',
        'backup'            => 'Back Up',
        'footer'            => 'EasyFlow-L: an Integrated Opioid Replacement Patient Management System – developed by LVCTHealth – Stawisha Pwani Project 2026 – © LVCT@20',
        'select_language'   => 'Select Language',
        'language'          => 'Language',
        'save'              => 'Save',
        'cancel'            => 'Cancel',
        'submit'            => 'Submit',
        'search'            => 'Search',
        'edit'              => 'Edit',
        'delete'            => 'Delete',
        'view'              => 'View',
        'add'               => 'Add',
        'update'            => 'Update',
        'close'             => 'Close',
        'yes'               => 'Yes',
        'no'                => 'No',
        'confirm'           => 'Confirm',
        'back'              => 'Back',
        'print'             => 'Print',
        'export'            => 'Export',
        'loading'           => 'Loading…',
        'error'             => 'Error',
        'success'           => 'Success',
        'warning'           => 'Warning',
        'required_field'    => 'This field is required',
        'date'              => 'Date',
        'time'              => 'Time',
        'status'            => 'Status',
        'active'            => 'Active',
        'inactive'          => 'Inactive',
        'name'              => 'Name',
        'actions'           => 'Actions',

        // ----- Dashboard / Nav -----
        'administrator'     => 'Administrator',
        'refresh'           => 'BackUp and Refresh',
        'pt_management'     => 'Patient Management',
        'dispensing_pharm'  => 'Dispensing Pharmacy',
        'clinician'         => 'Clinician',
        'psycho_support'    => 'Psychosocial Support',
        'lab_diagnosis'     => 'Laboratory and Diagnostics',
        'view_referrals'    => 'View Referrals',
        'reports_mgt'       => 'Reports Management',
        'biometrics'        => 'Biometrics',
        'profile'           => 'Profile',
        'appointments'      => 'Appointments',
        'pharmacovigilance' => 'Pharmacovigilance',
        'health_records'    => 'Health Records',
        'settings'          => 'Settings',
        'dashboard'         => 'Dashboard',
        'superadmin'        => 'Super Admin',
        'sops'              => 'SOPs',
        'training'          => 'Training Materials',

        // ----- Patient Management -----
        'patient'           => 'Patient',
        'patients'          => 'Patients',
        'add_patient'       => 'Add Patient',
        'edit_patient'      => 'Edit Patient',
        'patient_list'      => 'Patient List',
        'patient_details'   => 'Patient Details',
        'mat_id'            => 'MAT ID',
        'mat_number'        => 'MAT Number',
        'client_name'       => 'Client Name',
        'nickname'          => 'Nickname',
        'dob'               => 'Date of Birth',
        'age'               => 'Age',
        'sex'               => 'Sex',
        'gender'            => 'Gender',
        'male'              => 'Male',
        'female'            => 'Female',
        'address'           => 'Address',
        'phone'             => 'Phone',
        'enrolment_date'    => 'Enrolment Date',
        'next_appointment'  => 'Next Appointment',
        'last_visit'        => 'Last Visit',
        'peer_educator'     => 'Peer Educator',
        'cso'               => 'CSO',
        'drug_name'         => 'Drug Name',
        'dosage'            => 'Dosage',
        'current_status'    => 'Current Status',
        'transfer_in'       => 'Transfer In',
        'transfer_out'      => 'Transfer Out',
        'discharge'         => 'Discharge',
        'enroll'            => 'Enroll',
        'photo'             => 'Photo',
        'search_patient'    => 'Search Patient',
        'no_patients_found' => 'No patients found',

        // ----- Dispensing / Pharmacy -----
        'dispensing'        => 'Dispensing',
        'dispense'          => 'Dispense',
        'dispense_drug'     => 'Dispense Drug',
        'dispensing_date'   => 'Dispensing Date',
        'dispense_with_pump'=> 'Dispense with Pump',
        'routine_dispensing'=> 'Routine Dispensing',
        'pump_device'       => 'Pump Device',
        'pump_port'         => 'Pump Port',
        'pump_host'         => 'Pump Host / IP',
        'pump_direction'    => 'Pump Direction',
        'pump_normal'       => 'Normal (Forward)',
        'pump_reversed'     => 'Reversed (Backward)',
        'calibration_factor'=> 'Calibration Factor',
        'concentration'     => 'Concentration (mg/mL)',
        'volume_ml'         => 'Volume (mL)',
        'dosage_mg'         => 'Dosage (mg)',
        'days_to_appt'      => 'Days to Appointment',
        'missed_appointment'=> 'Missed Appointment',
        'refer_clinician'   => 'Refer to Clinician',
        'reason'            => 'Reason',
        'pharmacy_officer'  => 'Pharmacy Officer',
        'stock'             => 'Stock',
        'stock_in'          => 'Stock In',
        'stock_out'         => 'Stock Out',
        'current_stock'     => 'Current Stock',
        'out_of_stock'      => 'Out of Stock',
        'drug_list'         => 'Drug List',
        'add_drug'          => 'Add Drug',
        'dispensed_today'   => 'Already dispensed today',
        'dispense_success'  => 'Dispensed successfully',
        'dispense_failed'   => 'Dispensing failed',
        'pump_reservoir'    => 'Pump Reservoir',
        'reservoir_topup'   => 'Reservoir Top-Up',
        'remaining_mg'      => 'Remaining (mg)',
        'prescriptions'     => 'Prescriptions',
        'other_drugs'       => 'Other Drugs',
        'takeaway'          => 'Take-Away',

        // ----- Clinical -----
        'clinical_encounter'=> 'Clinical Encounter',
        'clinician_form'    => 'Clinician Form',
        'visit_date'        => 'Visit Date',
        'diagnosis'         => 'Diagnosis',
        'treatment'         => 'Treatment',
        'notes'             => 'Notes',
        'discontinuation'   => 'Discontinuation',
        'voluntary_disc'    => 'Voluntary Discontinuation',
        'involuntary_disc'  => 'Involuntary Discontinuation',

        // ----- Lab -----
        'laboratory'        => 'Laboratory',
        'lab_results'       => 'Lab Results',
        'test_type'         => 'Test Type',
        'result'            => 'Result',
        'sample_date'       => 'Sample Date',

        // ----- Appointments -----
        'schedule_appointment' => 'Schedule Appointment',
        'appointment_date'  => 'Appointment Date',
        'appointment_type'  => 'Appointment Type',
        'upcoming'          => 'Upcoming',
        'past'              => 'Past',
        'missed'            => 'Missed',

        // ----- Reports -----
        'reports'           => 'Reports',
        'monthly_report'    => 'Monthly Report',
        'daily_summary'     => 'Daily Summary',
        'moh_731'           => 'MOH 731 Report',
        'generate_report'   => 'Generate Report',
        'download_report'   => 'Download Report',
        'period'            => 'Period',
        'month'             => 'Month',
        'year'              => 'Year',
        'total'             => 'Total',
        'khis_submit'       => 'Submit to KHIS',
        'khis_report'       => 'KHIS Monthly Report',

        // ----- Psychosocial -----
        'psychosocial'      => 'Psychosocial',
        'counselling'       => 'Counselling',
        'assessment'        => 'Assessment',

        // ----- Biometrics -----
        'fingerprint'       => 'Fingerprint',
        'capture_fingerprint' => 'Capture Fingerprint',
        'verify_fingerprint'  => 'Verify Fingerprint',
        'face_recognition'  => 'Face Recognition',

        // ----- Referrals -----
        'referrals'         => 'Referrals',
        'refer_to'          => 'Refer To',
        'refer_from'        => 'Referred From',
        'referral_reason'   => 'Referral Reason',
        'referral_date'     => 'Referral Date',

        // ----- Pharmacovigilance -----
        'adverse_event'     => 'Adverse Event',
        'report_ae'         => 'Report Adverse Event',
        'ae_description'    => 'Event Description',
        'severity'          => 'Severity',
        'outcome'           => 'Outcome',

        // ----- User / Profile -----
        'user'              => 'User',
        'username'          => 'Username',
        'first_name'        => 'First Name',
        'last_name'         => 'Last Name',
        'email'             => 'Email',
        'change_password'   => 'Change Password',
        'new_password'      => 'New Password',
        'confirm_password'  => 'Confirm Password',

        // ----- Facility -----
        'facility'          => 'Facility',
        'facility_name'     => 'Facility Name',
        'mfl_code'          => 'MFL Code',
        'county'            => 'County',
        'sub_county'        => 'Sub-County',
    ],

    // =====================================================================
    // FRENCH
    // =====================================================================
    'fr' => [
        // ----- Global / Auth -----
        'login'             => 'Connexion',
        'usernname'         => 'Nom d\'utilisateur',
        'password'          => 'Mot de passe',
        'system_name'       => 'Système de gestion des patients',
        'about_us'          => 'À propos de nous',
        'welcome'           => 'Bienvenue',
        'logout'            => 'Déconnexion',
        'rolle'             => 'Rôle',
        'backup'            => 'Sauvegarde',
        'footer'            => 'EasyFlow-L: Système intégré de gestion des patients sous traitement de substitution aux opioïdes – développé par LVCTHealth – Projet Stawisha Pwani 2026 – © LVCT@20',
        'select_language'   => 'Choisir la langue',
        'language'          => 'Langue',
        'save'              => 'Enregistrer',
        'cancel'            => 'Annuler',
        'submit'            => 'Soumettre',
        'search'            => 'Rechercher',
        'edit'              => 'Modifier',
        'delete'            => 'Supprimer',
        'view'              => 'Voir',
        'add'               => 'Ajouter',
        'update'            => 'Mettre à jour',
        'close'             => 'Fermer',
        'yes'               => 'Oui',
        'no'                => 'Non',
        'confirm'           => 'Confirmer',
        'back'              => 'Retour',
        'print'             => 'Imprimer',
        'export'            => 'Exporter',
        'loading'           => 'Chargement…',
        'error'             => 'Erreur',
        'success'           => 'Succès',
        'warning'           => 'Avertissement',
        'required_field'    => 'Ce champ est obligatoire',
        'date'              => 'Date',
        'time'              => 'Heure',
        'status'            => 'Statut',
        'active'            => 'Actif',
        'inactive'          => 'Inactif',
        'name'              => 'Nom',
        'actions'           => 'Actions',

        // ----- Dashboard / Nav -----
        'administrator'     => 'Administrateur',
        'refresh'           => 'Sauvegarder et Actualiser',
        'pt_management'     => 'Gestion des patients',
        'dispensing_pharm'  => 'Pharmacie de dispensation',
        'clinician'         => 'Clinicien',
        'psycho_support'    => 'Soutien psychosocial',
        'lab_diagnosis'     => 'Laboratoire et diagnostics',
        'view_referrals'    => 'Voir les références',
        'reports_mgt'       => 'Gestion des rapports',
        'biometrics'        => 'Biométrie',
        'profile'           => 'Profil',
        'appointments'      => 'Rendez-vous',
        'pharmacovigilance' => 'Pharmacovigilance',
        'health_records'    => 'Dossiers de santé',
        'settings'          => 'Paramètres',
        'dashboard'         => 'Tableau de bord',
        'superadmin'        => 'Super Administrateur',
        'sops'              => 'Procédures opérationnelles',
        'training'          => 'Matériel de formation',

        // ----- Patient Management -----
        'patient'           => 'Patient',
        'patients'          => 'Patients',
        'add_patient'       => 'Ajouter un patient',
        'edit_patient'      => 'Modifier le patient',
        'patient_list'      => 'Liste des patients',
        'patient_details'   => 'Détails du patient',
        'mat_id'            => 'ID MAT',
        'mat_number'        => 'Numéro MAT',
        'client_name'       => 'Nom du client',
        'nickname'          => 'Surnom',
        'dob'               => 'Date de naissance',
        'age'               => 'Âge',
        'sex'               => 'Sexe',
        'gender'            => 'Genre',
        'male'              => 'Masculin',
        'female'            => 'Féminin',
        'address'           => 'Adresse',
        'phone'             => 'Téléphone',
        'enrolment_date'    => 'Date d\'inscription',
        'next_appointment'  => 'Prochain rendez-vous',
        'last_visit'        => 'Dernière visite',
        'peer_educator'     => 'Éducateur pair',
        'cso'               => 'Organisation de la société civile',
        'drug_name'         => 'Nom du médicament',
        'dosage'            => 'Dosage',
        'current_status'    => 'Statut actuel',
        'transfer_in'       => 'Transfert entrant',
        'transfer_out'      => 'Transfert sortant',
        'discharge'         => 'Sortie',
        'enroll'            => 'Inscrire',
        'photo'             => 'Photo',
        'search_patient'    => 'Rechercher un patient',
        'no_patients_found' => 'Aucun patient trouvé',

        // ----- Dispensing / Pharmacy -----
        'dispensing'        => 'Dispensation',
        'dispense'          => 'Dispenser',
        'dispense_drug'     => 'Dispenser le médicament',
        'dispensing_date'   => 'Date de dispensation',
        'dispense_with_pump'=> 'Dispenser avec la pompe',
        'routine_dispensing'=> 'Dispensation de routine',
        'pump_device'       => 'Appareil de pompe',
        'pump_port'         => 'Port de la pompe',
        'pump_host'         => 'Hôte de la pompe / IP',
        'pump_direction'    => 'Direction de la pompe',
        'pump_normal'       => 'Normale (vers l\'avant)',
        'pump_reversed'     => 'Inversée (vers l\'arrière)',
        'calibration_factor'=> 'Facteur de calibration',
        'concentration'     => 'Concentration (mg/mL)',
        'volume_ml'         => 'Volume (mL)',
        'dosage_mg'         => 'Dosage (mg)',
        'days_to_appt'      => 'Jours jusqu\'au rendez-vous',
        'missed_appointment'=> 'Rendez-vous manqué',
        'refer_clinician'   => 'Référer au clinicien',
        'reason'            => 'Raison',
        'pharmacy_officer'  => 'Agent de pharmacie',
        'stock'             => 'Stock',
        'stock_in'          => 'Entrée de stock',
        'stock_out'         => 'Sortie de stock',
        'current_stock'     => 'Stock actuel',
        'out_of_stock'      => 'Rupture de stock',
        'drug_list'         => 'Liste des médicaments',
        'add_drug'          => 'Ajouter un médicament',
        'dispensed_today'   => 'Déjà dispensé aujourd\'hui',
        'dispense_success'  => 'Dispensé avec succès',
        'dispense_failed'   => 'Échec de la dispensation',
        'pump_reservoir'    => 'Réservoir de la pompe',
        'reservoir_topup'   => 'Remplissage du réservoir',
        'remaining_mg'      => 'Restant (mg)',
        'prescriptions'     => 'Ordonnances',
        'other_drugs'       => 'Autres médicaments',
        'takeaway'          => 'Médicament à emporter',

        // ----- Clinical -----
        'clinical_encounter'=> 'Consultation clinique',
        'clinician_form'    => 'Formulaire du clinicien',
        'visit_date'        => 'Date de visite',
        'diagnosis'         => 'Diagnostic',
        'treatment'         => 'Traitement',
        'notes'             => 'Notes',
        'discontinuation'   => 'Arrêt du traitement',
        'voluntary_disc'    => 'Arrêt volontaire',
        'involuntary_disc'  => 'Arrêt involontaire',

        // ----- Lab -----
        'laboratory'        => 'Laboratoire',
        'lab_results'       => 'Résultats de laboratoire',
        'test_type'         => 'Type d\'analyse',
        'result'            => 'Résultat',
        'sample_date'       => 'Date du prélèvement',

        // ----- Appointments -----
        'schedule_appointment' => 'Planifier un rendez-vous',
        'appointment_date'  => 'Date du rendez-vous',
        'appointment_type'  => 'Type de rendez-vous',
        'upcoming'          => 'À venir',
        'past'              => 'Passé',
        'missed'            => 'Manqué',

        // ----- Reports -----
        'reports'           => 'Rapports',
        'monthly_report'    => 'Rapport mensuel',
        'daily_summary'     => 'Résumé quotidien',
        'moh_731'           => 'Rapport MOH 731',
        'generate_report'   => 'Générer le rapport',
        'download_report'   => 'Télécharger le rapport',
        'period'            => 'Période',
        'month'             => 'Mois',
        'year'              => 'Année',
        'total'             => 'Total',
        'khis_submit'       => 'Soumettre au KHIS',
        'khis_report'       => 'Rapport mensuel KHIS',

        // ----- Psychosocial -----
        'psychosocial'      => 'Psychosocial',
        'counselling'       => 'Counseling',
        'assessment'        => 'Évaluation',

        // ----- Biometrics -----
        'fingerprint'       => 'Empreinte digitale',
        'capture_fingerprint' => 'Capturer l\'empreinte',
        'verify_fingerprint'  => 'Vérifier l\'empreinte',
        'face_recognition'  => 'Reconnaissance faciale',

        // ----- Referrals -----
        'referrals'         => 'Références',
        'refer_to'          => 'Référer à',
        'refer_from'        => 'Référé de',
        'referral_reason'   => 'Motif de référence',
        'referral_date'     => 'Date de référence',

        // ----- Pharmacovigilance -----
        'adverse_event'     => 'Événement indésirable',
        'report_ae'         => 'Signaler un événement indésirable',
        'ae_description'    => 'Description de l\'événement',
        'severity'          => 'Gravité',
        'outcome'           => 'Issue',

        // ----- User / Profile -----
        'user'              => 'Utilisateur',
        'username'          => 'Nom d\'utilisateur',
        'first_name'        => 'Prénom',
        'last_name'         => 'Nom de famille',
        'email'             => 'E-mail',
        'change_password'   => 'Changer le mot de passe',
        'new_password'      => 'Nouveau mot de passe',
        'confirm_password'  => 'Confirmer le mot de passe',

        // ----- Facility -----
        'facility'          => 'Établissement',
        'facility_name'     => 'Nom de l\'établissement',
        'mfl_code'          => 'Code MFL',
        'county'            => 'Comté',
        'sub_county'        => 'Sous-comté',
    ]
];

// -------------------------------------------------------------------------
// Session / URL language handling
// -------------------------------------------------------------------------

// Ensure session is started before using $_SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Default language
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

// Allow language switch via ?lang=fr (or any supported code)
if (isset($_GET['lang']) && array_key_exists($_GET['lang'], $languages)) {
    $_SESSION['lang'] = $_GET['lang'];
    // Redirect to the same page without the lang param to keep URLs clean
    $redirect_url = strtok($_SERVER['REQUEST_URI'], '?');
    $params = $_GET;
    unset($params['lang']);
    if (!empty($params)) {
        $redirect_url .= '?' . http_build_query($params);
    }
    header('Location: ' . $redirect_url);
    exit;
}

$lang = $_SESSION['lang'];
$text = $languages[$lang];

/**
 * Helper: return translation for a key, falling back to English if missing.
 * Usage: t('dispensing')  — works even if $text is not in scope.
 */
if (!function_exists('t')) {
    function t(string $key): string {
        global $text, $languages;
        if (isset($text[$key])) return $text[$key];
        // fallback to English
        return $languages['en'][$key] ?? $key;
    }
}
?>
