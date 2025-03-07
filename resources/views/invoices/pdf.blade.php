<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture N°{{ $facture->id }}</title>
    <style>
        /* Réinitialisation de base */
        *, *::before, *::after {
            box-sizing: border-box;
        }
        html, body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #333;
        }

        /* Conteneur principal */
        .page {
            padding: 20px;
        }

        /* Header en tableau pour un alignement fiable */
        table.header {
            width: 100%;
            border-bottom: 2px solid #ccc;
            margin-bottom: 20px;
        }
        .header-left, .header-right {
            font-size: 11px;
            line-height: 1.4;
            vertical-align: top;
        }
        .header-left {
            width: 50%;
        }
        .header-right {
            width: 50%;
            text-align: right;
        }

        /* Tableau récap dans le bloc de droite */
        .recap table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-bottom: 10px;
        }
        .recap th,
        .recap td {
            border: 1px solid #ccc;
            padding: 4px;
            text-align: right;
        }

        /* Table des prestations */
        .services {
            margin-top: 20px;
        }
        .services table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        .services th, .services td {
            border: 1px solid #ccc;
            padding: 6px;
        }
        .services th {
            background-color: #f0f0f0;
            text-align: center;
        }

        /* Totaux */
        .totals {
            text-align: right;
            margin-top: 20px;
            font-size: 11px;
        }

        /* Coordonnées bancaires */
        .bank-details {
            margin-top: 20px;
            font-size: 10px;
            line-height: 1.4;
        }

        /* Informations de règlement */
        .reglement {
            margin-top: 20px;
            font-size: 11px;
            line-height: 1.4;
        }

        /* Footer */
        .footer {
            text-align: center;
            font-size: 9px;
            color: #777;
            border-top: 1px solid #ccc;
            padding-top: 10px;
            margin-top: 20px; /* on peut ajuster selon la place */
        }
    </style>
</head>
<body>
    <div class="page">
        <!-- Header sous forme de tableau -->
        <table class="header">
            <tr>
                <!-- Bloc gauche : Informations du détenteur -->
                <td class="header-left">
                    <strong>MEBARKI Younes</strong><br>
                    90 route de gournay<br>
                    93160 Noisy-le-grand<br>
                    Email: mebarki.younes93@gmail.com<br>
                    Téléphone: 0750258791<br>
                    SIREN: 940150691
                </td>
                <!-- Bloc droit : Récapitulatif et réceptionneur -->
                <td class="header-right">
                    <div class="recap">
                        <table>
                            <tr>
                                <th>N° Facture</th>
                                <th>Date de création</th>
                                <th>Lieu de création</th>
                            </tr>
                            <tr>
                                <td>{{ $facture->id }}</td>
                                <td>{{ $facture->created_at->format('d/m/Y') }}</td>
                                <td>Noisy-le-grand</td>
                            </tr>
                        </table>
                    </div>
                    <div class="receiver">
                        <strong>EBS</strong><br>
                        21 rue de Fecamp<br>
                        75012 Paris, France<br>
                        SIREN: 92042893500018
                    </div>
                </td>
            </tr>
        </table>

        <!-- Table des prestations de services -->
        <div class="services">
            <h2 style="font-size: 14px; margin-bottom: 8px;">Prestation de services</h2>
            <table>
                <thead>
                    <tr>
                        <th>Réf.</th>
                        <th>Date</th>
                        <th>Qté</th>
                        <th>PU HT</th>
                        <th>Total HT</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($prestations as $prestation)
                    <tr>
                        <td>{{ $prestation->id }}</td>
                        <td>{{ \Carbon\Carbon::parse($prestation->date_prestation)->format('d/m/Y') }}</td>
                        <td>{{ $prestation->nombre_heures }}</td>
                        <td>{{ number_format($facture->taux_horaire ?? 20, 2, ',', ' ') }} €</td>
                        <td>{{ number_format($prestation->nombre_heures * ($facture->taux_horaire ?? 20), 2, ',', ' ') }} €</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Récapitulatif des montants -->
        <div class="totals">
            <p><strong>Total HT :</strong> {{ number_format($facture->total_ht, 2, ',', ' ') }} €</p>
            <p><strong>Montant TVA (0%) :</strong> 0,00 €</p>
            <p><strong>Total TTC :</strong> {{ number_format($facture->total_ht, 2, ',', ' ') }} €</p>
            <p><strong>À payer :</strong> {{ number_format($facture->total_ht, 2, ',', ' ') }} €</p>
        </div>

        <!-- Coordonnées bancaires -->
        <div class="bank-details">
            <p>
                <strong>Coordonnées bancaires pour réception du paiement :</strong><br>
                Banque: BoursoBank, 44 rue Traversière, CS 80134, 92772 BOULOGNE-BILLANCOURT CEDEX, France<br>
                BIC / Swift: BOUS FRPP XXX<br>
                IBAN: FR76 4061 8804 4500 0407 6686 026<br>
                RIB: Banque: 40618, Guichet: 80445, N° de compte: 00040766860, Clé Rib: 26
            </p>
        </div>

        <!-- Informations de règlement -->
        <div class="reglement">
            <p><strong>Date limite de validité :</strong> {{ $facture->created_at->format('d/m/Y') }}</p>
            <p><strong>Date limite de règlement :</strong> Dès réception</p>
            <p><strong>Mode de règlement :</strong> Virement bancaire</p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Facture N° {{ $facture->id }} | Page 1/1</p>
            <p>MEBARKI Younes, 90 route de gournay, 93160 Noisy-le-grand</p>
        </div>
    </div>
</body>
</html>
