<?php

namespace App\Enums;

enum FactureStatut: string
{
    case EnAttenteEnvoi = 'en_attente_envoi';
    case EnAttentePaiement = 'en_attente_paiement';
    case Paye = 'payé';
}