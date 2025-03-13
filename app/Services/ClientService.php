<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Facades\Auth;

class ClientService extends BaseService
{
    public function getAll()
    {
        return $this->handleExceptions(function () {
            return Client::where('user_id', Auth::id())->get();
        }, "Erreur lors de la récupération des clients", "client");
    }

    public function create(array $data)
    {
        return $this->handleExceptions(function () use ($data) {
            $data['user_id'] = Auth::id();
            return Client::create($data);
        }, "Erreur lors de la création du client", "client");
    }

    public function update(Client $client, array $data)
    {
        return $this->handleExceptions(function () use ($client, $data) {
            $client->update($data);
            return $client;
        }, "Erreur lors de la mise à jour du client (ID: $client->id)", "client");
    }

    public function delete(Client $client)
    {
        return $this->handleExceptions(function () use ($client) {
            $client->delete();
            return $client;
        }, "Erreur lors de la suppression du client (ID: $client->id)", "client");
    }
}
