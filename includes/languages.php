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

        // ----- Dashboard Summary Widgets -----
        'patients_summary'      => 'Patients Summary',
        'daily_consumption_sum' => 'Daily Consumption Summary',
        'stocks_summary'        => 'Stocks Summary',
        'monthly_consumption_sum' => 'Monthly Consumption Summary',
        'patient_status'        => 'Patient Status',
        'total_number'          => 'Total Number',
        'pending_prescriptions' => 'Pending Prescriptions',
        'expected_to_visit_today'=> 'Expected to visit today',
        'cumulative_ever'       => 'Cumulative Ever',
        'ever_enrolled'         => 'Ever Enrolled',
        'weaned_off'            => 'Weaned Off',
        'defaulters'            => 'Defaulters',
        'lost_to_follow_up'     => 'Lost to Follow Up',
        'discontinued_stopped'  => 'Discontinued (Stopped)',
        'inmates'               => 'Inmates',
        'died'                  => 'Died',

        // ----- Dashboard Stock / Consumption Templates ({drug} and {date} are placeholders) -----
        'tpl_no_dispensed'      => 'No {drug} dispensed on {date}.',
        'tpl_dispensed'         => '{drug} dispensed on {date}:',
        'tpl_balance'           => '{drug} Balance:',
        'tpl_no_stock'          => 'No {drug} stock records found.',
        'tpl_disp_month'        => '{drug} Dispensed in the Month:',
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

        // ----- Dashboard Summary Widgets -----
        'patients_summary'      => 'Résumé des Clients',
        'daily_consumption_sum' => 'Résumé de Consommation Quotidienne',
        'stocks_summary'        => 'Résumé des Stocks',
        'monthly_consumption_sum' => 'Résumé de Consommation Mensuelle',
        'patient_status'        => 'Statut du Client',
        'total_number'          => 'Nombre Total',
        'pending_prescriptions' => 'Prescriptions en Attente',
        'expected_to_visit_today'=> 'Attendus aujourd’hui',
        'cumulative_ever'       => 'Cumul Total',
        'ever_enrolled'         => 'Jamais Inscrits',
        'weaned_off'            => 'Sevrés',
        'defaulters'            => 'Défaillants',
        'lost_to_follow_up'     => 'Perdus de Vue',
        'discontinued_stopped'  => 'Arrêté (Stoppé)',
        'inmates'               => 'Détenus',
        'died'                  => 'Décédés',

        // ----- Dashboard Stock / Consumption Templates ({drug} and {date} are placeholders) -----
        'tpl_no_dispensed'      => 'Aucun {drug} distribué le {date}.',
        'tpl_dispensed'         => '{drug} distribué le {date} :',
        'tpl_balance'           => 'Solde {drug} :',
        'tpl_no_stock'          => 'Aucun enregistrement de stock trouvé pour {drug}.',
        'tpl_disp_month'        => '{drug} distribué ce mois :',
    ],

    // =====================================================================
    // PORTUGUESE
    // =====================================================================
    'pt' => [
        // ----- Global / Auth -----
        'login'             => 'Entrar',
        'usernname'         => 'Nome de utilizador',
        'password'          => 'Senha',
        'system_name'       => 'Sistema de Gestão de Pacientes',
        'about_us'          => 'Sobre nós',
        'welcome'           => 'Bem-vindo',
        'logout'            => 'Sair',
        'rolle'             => 'Função',
        'backup'            => 'Cópia de segurança',
        'footer'            => 'EasyFlow-L: Sistema integrado de gestão de pacientes em tratamento de substituição de opioides – desenvolvido pela LVCTHealth – Projeto Stawisha Pwani 2026 – © LVCT@20',
        'select_language'   => 'Selecionar idioma',
        'language'          => 'Idioma',
        'save'              => 'Guardar',
        'cancel'            => 'Cancelar',
        'submit'            => 'Submeter',
        'search'            => 'Pesquisar',
        'edit'              => 'Editar',
        'delete'            => 'Eliminar',
        'view'              => 'Ver',
        'add'               => 'Adicionar',
        'update'            => 'Atualizar',
        'close'             => 'Fechar',
        'yes'               => 'Sim',
        'no'                => 'Não',
        'confirm'           => 'Confirmar',
        'back'              => 'Voltar',
        'print'             => 'Imprimir',
        'export'            => 'Exportar',
        'loading'           => 'A carregar…',
        'error'             => 'Erro',
        'success'           => 'Sucesso',
        'warning'           => 'Aviso',
        'required_field'    => 'Este campo é obrigatório',
        'date'              => 'Data',
        'time'              => 'Hora',
        'status'            => 'Estado',
        'active'            => 'Ativo',
        'inactive'          => 'Inativo',
        'name'              => 'Nome',
        'actions'           => 'Ações',

        // ----- Dashboard / Nav -----
        'administrator'     => 'Administrador',
        'refresh'           => 'Guardar e Atualizar',
        'pt_management'     => 'Gestão de Pacientes',
        'dispensing_pharm'  => 'Farmácia de Distribuição',
        'clinician'         => 'Clínico',
        'psycho_support'    => 'Apoio Psicossocial',
        'lab_diagnosis'     => 'Laboratório e Diagnósticos',
        'view_referrals'    => 'Ver Referências',
        'reports_mgt'       => 'Gestão de Relatórios',
        'biometrics'        => 'Biometria',
        'profile'           => 'Perfil',
        'appointments'      => 'Consultas',
        'pharmacovigilance' => 'Farmacovigilância',
        'health_records'    => 'Registos de Saúde',
        'settings'          => 'Definições',
        'dashboard'         => 'Painel',
        'superadmin'        => 'Super Administrador',
        'sops'              => 'Procedimentos Operacionais',
        'training'          => 'Materiais de Formação',

        // ----- Patient Management -----
        'patient'           => 'Paciente',
        'patients'          => 'Pacientes',
        'add_patient'       => 'Adicionar Paciente',
        'edit_patient'      => 'Editar Paciente',
        'patient_list'      => 'Lista de Pacientes',
        'patient_details'   => 'Detalhes do Paciente',
        'mat_id'            => 'ID MAT',
        'mat_number'        => 'Número MAT',
        'client_name'       => 'Nome do Cliente',
        'nickname'          => 'Alcunha',
        'dob'               => 'Data de Nascimento',
        'age'               => 'Idade',
        'sex'               => 'Sexo',
        'gender'            => 'Género',
        'male'              => 'Masculino',
        'female'            => 'Feminino',
        'address'           => 'Endereço',
        'phone'             => 'Telefone',
        'enrolment_date'    => 'Data de Inscrição',
        'next_appointment'  => 'Próxima Consulta',
        'last_visit'        => 'Última Visita',
        'peer_educator'     => 'Educador Par',
        'cso'               => 'Organização da Sociedade Civil',
        'drug_name'         => 'Nome do Medicamento',
        'dosage'            => 'Dosagem',
        'current_status'    => 'Estado Atual',
        'transfer_in'       => 'Transferência de Entrada',
        'transfer_out'      => 'Transferência de Saída',
        'discharge'         => 'Alta',
        'enroll'            => 'Inscrever',
        'photo'             => 'Fotografia',
        'search_patient'    => 'Pesquisar Paciente',
        'no_patients_found' => 'Nenhum paciente encontrado',

        // ----- Dispensing / Pharmacy -----
        'dispensing'        => 'Distribuição',
        'dispense'          => 'Distribuir',
        'dispense_drug'     => 'Distribuir Medicamento',
        'dispensing_date'   => 'Data de Distribuição',
        'dispense_with_pump'=> 'Distribuir com Bomba',
        'routine_dispensing'=> 'Distribuição de Rotina',
        'pump_device'       => 'Dispositivo de Bomba',
        'pump_port'         => 'Porta da Bomba',
        'pump_host'         => 'Host da Bomba / IP',
        'pump_direction'    => 'Direção da Bomba',
        'pump_normal'       => 'Normal (Avançar)',
        'pump_reversed'     => 'Invertida (Recuar)',
        'calibration_factor'=> 'Fator de Calibração',
        'concentration'     => 'Concentração (mg/mL)',
        'volume_ml'         => 'Volume (mL)',
        'dosage_mg'         => 'Dosagem (mg)',
        'days_to_appt'      => 'Dias para Consulta',
        'missed_appointment'=> 'Consulta Faltada',
        'refer_clinician'   => 'Referenciar ao Clínico',
        'reason'            => 'Motivo',
        'pharmacy_officer'  => 'Técnico de Farmácia',
        'stock'             => 'Stock',
        'stock_in'          => 'Entrada de Stock',
        'stock_out'         => 'Saída de Stock',
        'current_stock'     => 'Stock Atual',
        'out_of_stock'      => 'Sem Stock',
        'drug_list'         => 'Lista de Medicamentos',
        'add_drug'          => 'Adicionar Medicamento',
        'dispensed_today'   => 'Já distribuído hoje',
        'dispense_success'  => 'Distribuído com sucesso',
        'dispense_failed'   => 'Falha na distribuição',
        'pump_reservoir'    => 'Reservatório da Bomba',
        'reservoir_topup'   => 'Reabastecimento do Reservatório',
        'remaining_mg'      => 'Restante (mg)',
        'prescriptions'     => 'Prescrições',
        'other_drugs'       => 'Outros Medicamentos',
        'takeaway'          => 'Medicamento para Levar',

        // ----- Clinical -----
        'clinical_encounter'=> 'Consulta Clínica',
        'clinician_form'    => 'Formulário do Clínico',
        'visit_date'        => 'Data de Visita',
        'diagnosis'         => 'Diagnóstico',
        'treatment'         => 'Tratamento',
        'notes'             => 'Notas',
        'discontinuation'   => 'Interrupção do Tratamento',
        'voluntary_disc'    => 'Interrupção Voluntária',
        'involuntary_disc'  => 'Interrupção Involuntária',

        // ----- Lab -----
        'laboratory'        => 'Laboratório',
        'lab_results'       => 'Resultados de Laboratório',
        'test_type'         => 'Tipo de Análise',
        'result'            => 'Resultado',
        'sample_date'       => 'Data de Colheita',

        // ----- Appointments -----
        'schedule_appointment' => 'Agendar Consulta',
        'appointment_date'  => 'Data da Consulta',
        'appointment_type'  => 'Tipo de Consulta',
        'upcoming'          => 'Próximas',
        'past'              => 'Anteriores',
        'missed'            => 'Faltadas',

        // ----- Reports -----
        'reports'           => 'Relatórios',
        'monthly_report'    => 'Relatório Mensal',
        'daily_summary'     => 'Resumo Diário',
        'moh_731'           => 'Relatório MOH 731',
        'generate_report'   => 'Gerar Relatório',
        'download_report'   => 'Descarregar Relatório',
        'period'            => 'Período',
        'month'             => 'Mês',
        'year'              => 'Ano',
        'total'             => 'Total',
        'khis_submit'       => 'Submeter ao KHIS',
        'khis_report'       => 'Relatório Mensal KHIS',

        // ----- Psychosocial -----
        'psychosocial'      => 'Psicossocial',
        'counselling'       => 'Aconselhamento',
        'assessment'        => 'Avaliação',

        // ----- Biometrics -----
        'fingerprint'       => 'Impressão Digital',
        'capture_fingerprint' => 'Capturar Impressão',
        'verify_fingerprint'  => 'Verificar Impressão',
        'face_recognition'  => 'Reconhecimento Facial',

        // ----- Referrals -----
        'referrals'         => 'Referências',
        'refer_to'          => 'Referenciar para',
        'refer_from'        => 'Referenciado de',
        'referral_reason'   => 'Motivo da Referência',
        'referral_date'     => 'Data da Referência',

        // ----- Pharmacovigilance -----
        'adverse_event'     => 'Evento Adverso',
        'report_ae'         => 'Reportar Evento Adverso',
        'ae_description'    => 'Descrição do Evento',
        'severity'          => 'Gravidade',
        'outcome'           => 'Resultado',

        // ----- User / Profile -----
        'user'              => 'Utilizador',
        'username'          => 'Nome de utilizador',
        'first_name'        => 'Primeiro Nome',
        'last_name'         => 'Apelido',
        'email'             => 'E-mail',
        'change_password'   => 'Alterar Senha',
        'new_password'      => 'Nova Senha',
        'confirm_password'  => 'Confirmar Senha',

        // ----- Facility -----
        'facility'          => 'Unidade de Saúde',
        'facility_name'     => 'Nome da Unidade',
        'mfl_code'          => 'Código MFL',
        'county'            => 'Condado',
        'sub_county'        => 'Sub-condado',

        // ----- Dashboard Summary Widgets -----
        'patients_summary'      => 'Resumo de Clientes',
        'daily_consumption_sum' => 'Resumo de Consumo Diário',
        'stocks_summary'        => 'Resumo de Stock',
        'monthly_consumption_sum' => 'Resumo de Consumo Mensal',
        'patient_status'        => 'Estado do Cliente',
        'total_number'          => 'Número Total',
        'pending_prescriptions' => 'Prescrições Pendentes',
        'expected_to_visit_today'=> 'Esperados hoje',
        'cumulative_ever'       => 'Cumulativo Total',
        'ever_enrolled'         => 'Alguma Vez Inscritos',
        'weaned_off'            => 'Desmamados',
        'defaulters'            => 'Incumpridores',
        'lost_to_follow_up'     => 'Perdidos de Seguimento',
        'discontinued_stopped'  => 'Descontinuado (Parado)',
        'inmates'               => 'Reclusos',
        'died'                  => 'Falecidos',

        // ----- Dashboard Stock / Consumption Templates ({drug} and {date} are placeholders) -----
        'tpl_no_dispensed'      => 'Nenhum {drug} dispensado em {date}.',
        'tpl_dispensed'         => '{drug} dispensado em {date}:',
        'tpl_balance'           => 'Saldo de {drug}:',
        'tpl_no_stock'          => 'Nenhum registo de stock encontrado para {drug}.',
        'tpl_disp_month'        => '{drug} dispensado no mês:',
    ],
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

/**
 * Helper: translate a key then substitute placeholders, e.g.
 * tf('tpl_dispensed', ['{drug}' => 'Methadone', '{date}' => $displayDate])
 */
if (!function_exists('tf')) {
    function tf(string $key, array $replacements): string {
        return strtr(t($key), $replacements);
    }
}
?>
