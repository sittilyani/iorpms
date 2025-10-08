<?php
  include '../includes/config.php';
  include '../includes/header.php';
?><!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
    <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
            <meta name="generator" content="PhpSpreadsheet, https://github.com/PHPOffice/PhpSpreadsheet">
            <meta name="author" content="Dr Lyani Simon Sitti" />
            <title>LabReport</title>
            <link rel="stylesheet" href="style.css" type="text/css">
    </head>

    <body>
<style>
@page { margin-left: 0.23622047244094in; margin-right: 0.23622047244094in; margin-top: 0.90551181102362in; margin-bottom: 0.74803149606299in; }
body { margin-left: 0.23622047244094in; margin-right: 0.23622047244094in; margin-top: 0.90551181102362in; margin-bottom: 0.74803149606299in; }
</style>
        <table border="0" cellpadding="0" cellspacing="0" id="sheet0" class="sheet0">
                <col class="col0">
                <col class="col1">
                <col class="col2">
                <col class="col3">
                <col class="col4">
                <col class="col5">
                <col class="col6">
                <col class="col7">
                <col class="col8">
                <tbody>
                    <tr class="row0">
                        <td class="column0">&nbsp;</td>
                        <td class="column1">&nbsp;</td>
                        <td class="column2">&nbsp;</td>
                        <td class="column3">&nbsp;</td>
                        <td class="column4">&nbsp;</td>
                        <td class="column5">&nbsp;</td>
                        <td class="column6">&nbsp;</td>
                        <td class="column7">&nbsp;</td>
                        <td class="column8">&nbsp;</td>
                    </tr>
                    <tr class="row1">
                        <td class="column0 style6 s style6" colspan="9">Monthly Methadone Assisted Therapy Laboratory Report</td>
                    </tr>
                    <tr class="row2">
                        <td class="column0 style7 s">Patient Category</td>
                        <td class="column1 style8 s style8" colspan="4">People who use Drugs (PWUDs)</td>
                        <td class="column5 style8 s style8" colspan="4">People Who Inject Drugs (PWIDs)</td>
                    </tr>
                    <tr class="row3">
                        <td class="column0 style2 null"></td>
                        <td class="column1 style2 s">Male</td>
                        <td class="column2 style2 s">Female</td>
                        <td class="column3 style2 s">Other</td>
                        <td class="column4 style2 s">Total</td>
                        <td class="column5 style2 s">Male</td>
                        <td class="column6 style2 s">Female</td>
                        <td class="column7 style2 s">Other</td>
                        <td class="column8 style2 s">Total</td>
                    </tr>
                    <tr class="row4">
                        <td class="column0 style2 s">Individuals tested -ve for HIV</td>
                        <td class="column1 style2 null" style="text-align: center"><?php include 'HIV_negPWUD_Male.php'; ?></td>
                        <td class="column2 style2 null" style="text-align: center"><?php include 'HIV_negPWUD_Female.php'; ?></td>
                        <td class="column3 style2 null" style="text-align: center"><?php include 'HIV_negPWUD_Other.php'; ?></td>
                        <td class="column4 style2 null" style="text-align: center"></td>
                        <td class="column5 style2 null" style="text-align: center"><?php include 'HIV_negPWID_Male.php'; ?></td>
                        <td class="column6 style2 null" style="text-align: center"><?php include 'HIV_negPWID_Female.php'; ?></td>
                        <td class="column7 style2 null" style="text-align: center"><?php include 'HIV_negPWID_Other.php'; ?></td>
                        <td class="column8 style2 null" style="text-align: center;"></td>
                    </tr>
                    <tr class="row5">
                        <td class="column0 style2 s">Individuals tested +ve for HIV</td>
                        <td class="column1 style2 null" style="text-align: center;"><?php include 'HIV_posPWUD_Male.php'; ?></td>
                        <td class="column2 style2 null" style="text-align: center;"><?php include 'HIV_posPWUD_Female.php'; ?></td>
                        <td class="column3 style2 null" style="text-align: center;"><?php include 'HIV_posPWUD_Other.php'; ?></td>
                        <td class="column4 style2 null" style="text-align: center;"></td>
                        <td class="column5 style2 null" style="text-align: center;"><?php include 'HIV_posPWID_Male.php'; ?></td>
                        <td class="column6 style2 null" style="text-align: center;"><?php include 'HIV_posPWID_Female.php'; ?></td>
                        <td class="column7 style2 null" style="text-align: center;"><?php include 'HIV_posPWID_Other.php'; ?></td>
                        <td class="column8 style2 null" style="text-align: center;"></td>
                    </tr>
                    <tr class="row6">
                        <td class="column0 style2 s">Individuals tested -ve for HBsAg</td>
                        <td class="column1 style2 null" style="text-align: center;"><?php include 'HepB_negPWUD_Male.php'; ?></td>
                        <td class="column2 style2 null" style="text-align: center;"><?php include 'HepB_negPWUD_Female.php'; ?></td>
                        <td class="column3 style2 null" style="text-align: center;"><?php include 'HepB_negPWUD_Other.php'; ?></td>
                        <td class="column4 style2 null" style="text-align: center;"></td>
                        <td class="column5 style2 null" style="text-align: center;"><?php include 'HepB_negPWID_Male.php'; ?></td>
                        <td class="column6 style2 null" style="text-align: center;"><?php include 'HepB_negPWID_Female.php'; ?></td>
                        <td class="column7 style2 null" style="text-align: center;"><?php include 'HepB_negPWID_Other.php'; ?></td>
                        <td class="column8 style2 null" style="text-align: center;"></td>
                    </tr>
                    <tr class="row7">
                        <td class="column0 style2 s">Individuals tested +ve for HBsAg</td>
                        <td class="column1 style2 null" style="text-align: center;"><?php include 'HepB_posPWUD_Male.php'; ?></td>
                        <td class="column2 style2 null" style="text-align: center;"><?php include 'HepB_posPWUD_Female.php'; ?></td>
                        <td class="column3 style2 null" style="text-align: center;"><?php include 'HepB_posPWUD_Other.php'; ?></td>
                        <td class="column4 style2 null" style="text-align: center;"></td>
                        <td class="column5 style2 null" style="text-align: center;"><?php include 'HepB_posPWID_Male.php'; ?></td>
                        <td class="column6 style2 null" style="text-align: center;"><?php include 'HepB_posPWID_Female.php'; ?></td>
                        <td class="column7 style2 null" style="text-align: center;"><?php include 'HepB_posPWID_Other.php'; ?></td>
                        <td class="column8 style2 null" style="text-align: center;"></td>
                    </tr>
                    <tr class="row8">
                        <td class="column0 style2 s">Individuals tested -ve HepC</td>
                        <td class="column1 style2 null" style="text-align: center;"><?php include 'HepC_negPWUD_Male.php'; ?></td>
                        <td class="column2 style2 null" style="text-align: center;"><?php include 'HepC_negPWUD_Female.php'; ?></td>
                        <td class="column3 style2 null" style="text-align: center;"><?php include 'HepC_negPWUD_Other.php'; ?></td>
                        <td class="column4 style2 null" style="text-align: center;"></td>
                        <td class="column5 style2 null" style="text-align: center;"><?php include 'HepC_negPWID_Male.php'; ?></td>
                        <td class="column6 style2 null" style="text-align: center;"><?php include 'HepC_negPWID_Female.php'; ?></td>
                        <td class="column7 style2 null" style="text-align: center;"><?php include 'HepC_negPWID_Other.php'; ?></td>
                        <td class="column8 style2 null" style="text-align: center;"></td>
                    </tr>
                    <tr class="row9">
                        <td class="column0 style2 s">Individuals tested +ve for HepC</td>
                        <td class="column1 style2 null" style="text-align: center;"><?php include 'HepC_posPWUD_Male.php'; ?></td>
                        <td class="column2 style2 null" style="text-align: center;"><?php include 'HepC_posPWUD_Female.php'; ?></td>
                        <td class="column3 style2 null" style="text-align: center;"><?php include 'HepC_posPWUD_Other.php'; ?></td>
                        <td class="column4 style2 null" style="text-align: center;"></td>
                        <td class="column5 style2 null" style="text-align: center;"><?php include 'HepC_posPWID_Male.php'; ?></td>
                        <td class="column6 style2 null" style="text-align: center;"><?php include 'HepC_posPWID_Female.php'; ?></td>
                        <td class="column7 style2 null" style="text-align: center;"><?php include 'HepC_posPWID_Other.php'; ?></td>
                        <td class="column8 style2 null" style="text-align: center;"></td>
                    </tr>
                    <tr class="row10">
                        <td class="column0 style2 s">Individuals tested for -ve for Malaria</td>
                        <td class="column1 style2 null" style="text-align: center;"><?php include 'mal_negPWUD_Male.php'; ?></td>
                        <td class="column2 style2 null" style="text-align: center;"><?php include 'mal_negPWUD_Female.php'; ?></td>
                        <td class="column3 style2 null" style="text-align: center;"><?php include 'mal_negPWUD_Other.php'; ?></td>
                        <td class="column4 style2 null" style="text-align: center;"></td>
                        <td class="column5 style2 null" style="text-align: center;"><?php include 'mal_negPWID_Male.php'; ?></td>
                        <td class="column6 style2 null" style="text-align: center;"><?php include 'mal_negPWID_Female.php'; ?></td>
                        <td class="column7 style2 null" style="text-align: center;"><?php include 'mal_negPWID_Other.php'; ?></td>
                        <td class="column8 style2 null" style="text-align: center;"></td>
                    </tr>
                    <tr class="row11">
                        <td class="column0 style2 s">Individuals tested +ve for Malaria</td>
                        <td class="column1 style2 null" style="text-align: center;"><?php include 'mal_posPWUD_Male.php'; ?></td>
                        <td class="column2 style2 null" style="text-align: center;"><?php include 'mal_posPWUD_Female.php'; ?></td>
                        <td class="column3 style2 null" style="text-align: center;"><?php include 'mal_posPWUD_Other.php'; ?></td>
                        <td class="column4 style2 null" style="text-align: center;"></td>
                        <td class="column5 style2 null" style="text-align: center;"><?php include 'mal_posPWID_Male.php'; ?></td>
                        <td class="column6 style2 null" style="text-align: center;"><?php include 'mal_posPWID_Female.php'; ?></td>
                        <td class="column7 style2 null" style="text-align: center;"><?php include 'mal_posPWID_Other.php'; ?></td>
                        <td class="column8 style2 null" style="text-align: center;"></td>
                    </tr>
                    <tr class="row12">
                        <td class="column0 style2 s">Individuals tested for -ve for Pregnancy</td>
                        <td class="column1 style2 null" style="background-color: black"><?php include 'preg_negPWUD_Male.php'; ?></td>
                        <td class="column2 style2 null" style="text-align: center;"><?php include 'preg_negPWUD_Female.php'; ?></td>
                        <td class="column3 style2 null" style="text-align: center;"><?php include 'preg_negPWUD_Other.php'; ?></td>
                        <td class="column4 style2 null" style="text-align: center;"></td>
                        <td class="column5 style2 null" style="background-color: black"><?php include 'preg_negPWID_Male.php'; ?></td>
                        <td class="column6 style2 null" style="text-align: center;"><?php include 'preg_negPWID_Female.php'; ?></td>
                        <td class="column7 style2 null" style="text-align: center;"><?php include 'preg_negPWID_Other.php'; ?></td>
                        <td class="column8 style2 null" style="text-align: center;"></td>
                    </tr>
                    <tr class="row13">
                        <td class="column0 style2 s">Individuals tested +ve for Pregnancy</td>
                        <td class="column1 style2 null" style="background-color: black"><?php include 'preg_posPWUD_Male.php'; ?></td>
                        <td class="column2 style2 null" style="text-align: center;"><?php include 'preg_posPWUD_Female.php'; ?></td>
                        <td class="column3 style2 null" style="text-align: center;"><?php include 'preg_posPWUD_Other.php'; ?></td>
                        <td class="column4 style2 null" style="text-align: center;"></td>
                        <td class="column5 style2 null" style="background-color: black"><?php include 'preg_posPWID_Male.php'; ?></td>
                        <td class="column6 style2 null" style="text-align: center;"><?php include 'preg_posPWID_Female.php'; ?></td>
                        <td class="column7 style2 null" style="text-align: center;"><?php include 'preg_posPWID_Other.php'; ?></td>
                        <td class="column8 style2 null" style="text-align: center;"></td>
                    </tr>
                    <tr class="row14">
                        <td class="column0 style2 s">Individuals tested -ve for Syphillis (VDRL)</td>
                        <td class="column1 style2 null" style="text-align: center;"><?php include 'vdrl_negPWUD_Male.php'; ?></td>
                        <td class="column2 style2 null" style="text-align: center;"><?php include 'vdrl_negPWUD_Female.php'; ?></td>
                        <td class="column3 style2 null" style="text-align: center;"><?php include 'vdrl_negPWUD_Other.php'; ?></td>
                        <td class="column4 style2 null" style="text-align: center;"></td>
                        <td class="column5 style2 null" style="text-align: center;"><?php include 'vdrl_negPWID_Male.php'; ?></td>
                        <td class="column6 style2 null" style="text-align: center;"><?php include 'vdrl_negPWID_Female.php'; ?></td>
                        <td class="column7 style2 null" style="text-align: center;"><?php include 'vdrl_negPWID_Other.php'; ?></td>
                        <td class="column8 style2 null" style="text-align: center;"></td>
                    </tr>
                    <tr class="row15">
                        <td class="column0 style2 s">Individuals tested +ve for Syphillis (VDRL)</td>
                        <td class="column1 style2 null" style="text-align: center;"><?php include 'vdrl_posPWUD_Male.php'; ?></td>
                        <td class="column2 style2 null" style="text-align: center;"><?php include 'vdrl_posPWUD_Female.php'; ?></td>
                        <td class="column3 style2 null" style="text-align: center;"><?php include 'vdrl_posPWUD_Other.php'; ?></td>
                        <td class="column4 style2 null" style="text-align: center;"></td>
                        <td class="column5 style2 null" style="text-align: center;"><?php include 'vdrl_posPWID_Male.php'; ?></td>
                        <td class="column6 style2 null" style="text-align: center;"><?php include 'vdrl_posPWID_Female.php'; ?></td>
                        <td class="column7 style2 null" style="text-align: center;"><?php include 'vdrl_posPWID_Other.php'; ?></td>
                        <td class="column8 style2 null" style="text-align: center;"></td>
                    </tr>
                    <tr class="row16">
                        <td class="column0 style2 s">Individuals tested for -ve Urinalysis</td>
                        <td class="column1 style2 null" style="text-align: center;"><?php include 'uri_negPWUD_Male.php'; ?></td>
                        <td class="column2 style2 null" style="text-align: center;"><?php include 'uri_negPWUD_Female.php'; ?></td>
                        <td class="column3 style2 null" style="text-align: center;"><?php include 'uri_negPWUD_Other.php'; ?></td>
                        <td class="column4 style2 null" style="text-align: center;"></td>
                        <td class="column5 style2 null" style="text-align: center;"><?php include 'uri_negPWID_Male.php'; ?></td>
                        <td class="column6 style2 null" style="text-align: center;"><?php include 'uri_negPWID_Female.php'; ?></td>
                        <td class="column7 style2 null" style="text-align: center;"><?php include 'uri_negPWID_Other.php'; ?></td>
                        <td class="column8 style2 null" style="text-align: center;"></td>
                    </tr>
                    <tr class="row17">
                        <td class="column0 style2 s">Individuals tested +ve for Urinalysis</td>
                        <td class="column1 style2 null" style="text-align: center;"><?php include 'uri_posPWUD_Male.php'; ?></td>
                        <td class="column2 style2 null" style="text-align: center;"><?php include 'uri_posPWUD_Female.php'; ?></td>
                        <td class="column3 style2 null" style="text-align: center;"><?php include 'uri_posPWUD_Other.php'; ?></td>
                        <td class="column4 style2 null" style="text-align: center;"></td>
                        <td class="column5 style2 null" style="text-align: center;"><?php include 'uri_posPWID_Male.php'; ?></td>
                        <td class="column6 style2 null" style="text-align: center;"><?php include 'uri_posPWID_Female.php'; ?></td>
                        <td class="column7 style2 null" style="text-align: center;"><?php include 'uri_posPWID_Other.php'; ?></td>
                        <td class="column8 style2 null" style="text-align: center;"></td>
                    </tr>
                    <tr class="row18">
                        <td class="column0 style3 s style5" colspan="9">TOXICOLOGY</td>
                    </tr>
                    <tr class="row19">
                        <td class="column0 style2 s">Individuals tested +ve for Amphetamine</td>
                        <td class="column1 style2 null" style="text-align: center;"><?php include 'ampheth_posPWUD_Male.php'; ?></td>
                        <td class="column2 style2 null" style="text-align: center;"><?php include 'ampheth_posPWUD_Female.php'; ?></td>
                        <td class="column3 style2 null" style="text-align: center;"><?php include 'ampheth_posPWUD_Other.php'; ?></td>
                        <td class="column4 style2 null" style="text-align: center;"></td>
                        <td class="column5 style2 null" style="text-align: center;"><?php include 'ampheth_posPWID_Male.php'; ?></td>
                        <td class="column6 style2 null" style="text-align: center;"><?php include 'ampheth_posPWID_Female.php'; ?></td>
                        <td class="column7 style2 null" style="text-align: center;"><?php include 'ampheth_posPWID_Other.php'; ?></td>
                        <td class="column8 style2 null" style="text-align: center;"></td>
                    </tr>
                    <tr class="row20">
                        <td class="column0 style2 s">Individuals tested +ve for Metamphetamine</td>
                        <td class="column1 style2 null" style="text-align: center;"><?php include 'Metampheth_posPWUD_Male.php'; ?></td>
                        <td class="column2 style2 null" style="text-align: center;"><?php include 'Metampheth_posPWUD_Female.php'; ?></td>
                        <td class="column3 style2 null" style="text-align: center;"><?php include 'Metampheth_posPWUD_Other.php'; ?></td>
                        <td class="column4 style2 null" style="text-align: center;"></td>
                        <td class="column5 style2 null" style="text-align: center;"><?php include 'Metampheth_posPWID_Male.php'; ?></td>
                        <td class="column6 style2 null" style="text-align: center;"><?php include 'Metampheth_posPWID_Female.php'; ?></td>
                        <td class="column7 style2 null" style="text-align: center;"><?php include 'Metampheth_posPWID_Other.php'; ?></td>
                        <td class="column8 style2 null" style="text-align: center;"></td>
                    </tr>
                    <tr class="row21">
                        <td class="column0 style2 s">Individuals tested +ve for Morphine (Opiates)</td>
                        <td class="column1 style2 null" style="text-align: center;"><?php include 'Morph_posPWUD_Male.php'; ?></td>
                        <td class="column2 style2 null" style="text-align: center;"><?php include 'Morph_posPWUD_Female.php'; ?></td>
                        <td class="column3 style2 null" style="text-align: center;"><?php include 'Morph_posPWUD_Other.php'; ?></td>
                        <td class="column4 style2 null" style="text-align: center;"></td>
                        <td class="column5 style2 null" style="text-align: center;"><?php include 'Morph_posPWID_Male.php'; ?></td>
                        <td class="column6 style2 null" style="text-align: center;"><?php include 'Morph_posPWID_Female.php'; ?></td>
                        <td class="column7 style2 null" style="text-align: center;"><?php include 'Morph_posPWID_Other.php'; ?></td>
                        <td class="column8 style2 null" style="text-align: center;"></td>
                    </tr>
                    <tr class="row22">
                        <td class="column0 style2 s">Individuals tested +ve for Barbiturates</td>
                        <td class="column1 style2 null" style="text-align: center;"><?php include 'Barbs_posPWUD_Male.php'; ?></td>
                        <td class="column2 style2 null" style="text-align: center;"><?php include 'Barbs_posPWUD_Female.php'; ?></td>
                        <td class="column3 style2 null" style="text-align: center;"><?php include 'Barbs_posPWUD_Other.php'; ?></td>
                        <td class="column4 style2 null" style="text-align: center;"></td>
                        <td class="column5 style2 null" style="text-align: center;"><?php include 'Barbs_posPWID_Male.php'; ?></td>
                        <td class="column6 style2 null" style="text-align: center;"><?php include 'Barbs_posPWID_Female.php'; ?></td>
                        <td class="column7 style2 null" style="text-align: center;"><?php include 'Barbs_posPWID_Other.php'; ?></td>
                        <td class="column8 style2 null" style="text-align: center;"></td>
                    </tr>
                    <tr class="row23">
                        <td class="column0 style2 s">Individuals tested +ve for Cocaine</td>
                        <td class="column1 style2 null" style="text-align: center;"><?php include 'coca_posPWUD_Male.php'; ?></td>
                        <td class="column2 style2 null" style="text-align: center;"><?php include 'coca_posPWUD_Female.php'; ?></td>
                        <td class="column3 style2 null" style="text-align: center;"><?php include 'coca_posPWUD_Other.php'; ?></td>
                        <td class="column4 style2 null" style="text-align: center;"></td>
                        <td class="column5 style2 null" style="text-align: center;"><?php include 'coca_posPWID_Male.php'; ?></td>
                        <td class="column6 style2 null" style="text-align: center;"><?php include 'coca_posPWID_Female.php'; ?></td>
                        <td class="column7 style2 null" style="text-align: center;"><?php include 'coca_posPWID_Other.php'; ?></td>
                        <td class="column8 style2 null" style="text-align: center;"></td>
                    </tr>
                    <tr class="row24">
                        <td class="column0 style2 s">Individuals tested +ve for Codeine</td>
                        <td class="column1 style2 null" style="text-align: center;"><?php include 'codeine_posPWUD_Male.php'; ?></td>
                        <td class="column2 style2 null" style="text-align: center;"><?php include 'codeine_posPWUD_Female.php'; ?></td>
                        <td class="column3 style2 null" style="text-align: center;"><?php include 'codeine_posPWUD_Other.php'; ?></td>
                        <td class="column4 style2 null" style="text-align: center;"></td>
                        <td class="column5 style2 null" style="text-align: center;"><?php include 'codeine_posPWID_Male.php'; ?></td>
                        <td class="column6 style2 null" style="text-align: center;"><?php include 'codeine_posPWID_Female.php'; ?></td>
                        <td class="column7 style2 null" style="text-align: center;"><?php include 'codeine_posPWID_other.php'; ?></td>
                        <td class="column8 style2 null" style="text-align: center;"></td>
                    </tr>
                    <tr class="row25">
                        <td class="column0 style2 s">Individuals tested +ve for Benzodiazepines</td>
                        <td class="column1 style2 null" style="text-align: center;"><?php include 'bdz_posPWUD_Male.php'; ?></td>
                        <td class="column2 style2 null" style="text-align: center;"><?php include 'bdz_posPWUD_Female.php'; ?></td>
                        <td class="column3 style2 null" style="text-align: center;"><?php include 'bdz_posPWUD_Other.php'; ?></td>
                        <td class="column4 style2 null" style="text-align: center;"></td>
                        <td class="column5 style2 null" style="text-align: center;"><?php include 'bdz_posPWID_Male.php'; ?></td>
                        <td class="column6 style2 null" style="text-align: center;"><?php include 'bdz_posPWID_Female.php'; ?></td>
                        <td class="column7 style2 null" style="text-align: center;"><?php include 'bdz_posPWID_Other.php'; ?></td>
                        <td class="column8 style2 null" style="text-align: center;"></td>
                    </tr>
                    <tr class="row26">
                        <td class="column0 style2 s">Individuals tested +ve for Cannabis (Marijuana)</td>
                        <td class="column1 style2 null" style="text-align: center;"><?php include 'marijuana_posPWUD_Male.php'; ?></td>
                        <td class="column2 style2 null" style="text-align: center;"><?php include 'marijuana_posPWUD_Female.php'; ?></td>
                        <td class="column3 style2 null" style="text-align: center;"><?php include 'marijuana_posPWUD_Other.php'; ?></td>
                        <td class="column4 style2 null" style="text-align: center;"></td>
                        <td class="column5 style2 null" style="text-align: center;"><?php include 'marijuana_posPWID_Male.php'; ?></td>
                        <td class="column6 style2 null" style="text-align: center;"><?php include 'marijuana_posPWID_Female.php'; ?></td>
                        <td class="column7 style2 null" style="text-align: center;"><?php include 'marijuana_posPWID_Other.php'; ?></td>
                        <td class="column8 style2 null" style="text-align: center;"></td>
                    </tr>
                    <tr class="row27">
                        <td class="column0 style2 s">Individuals tested +ve for Amitriptyline</td>
                        <td class="column1 style2 null" style="text-align: center;"><?php include 'amitri_posPWUD_Male.php'; ?></td>
                        <td class="column2 style2 null" style="text-align: center;"><?php include 'amitri_posPWUD_Female.php'; ?></td>
                        <td class="column3 style2 null" style="text-align: center;"><?php include 'amitri_posPWUD_Other.php'; ?></td>
                        <td class="column4 style2 null" style="text-align: center;"></td>
                        <td class="column5 style2 null" style="text-align: center;"><?php include 'amitri_posPWID_Male.php'; ?></td>
                        <td class="column6 style2 null" style="text-align: center;"><?php include 'amitri_posPWID_Female.php'; ?></td>
                        <td class="column7 style2 null" style="text-align: center;"><?php include 'amitri_posPWID_Other.php'; ?></td>
                        <td class="column8 style2 null" style="text-align: center;"></td>
                    </tr>
                </tbody>
        </table>
    </body>
</html>
