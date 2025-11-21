<?php

namespace App\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema:'ProductInput',
    title:'ProductInput',
    description:'Données requises pour créer ou modifier un produit',
    properties:[
        new OA\Property(
            property:'name',
            type:'string',
            example:'Laptop Dell XPS 13',
            description:'Nom du produit'
        ),
        new OA\Property(
            property: 'price',
            type: 'number',
            format: 'float',
            example: 1299.99,
            description: 'Prix unitaire du produit.'
        ),
        new OA\Property(
            property: 'quantity',
            type: 'integer',
            example: 50,
            description: 'Quantité en stock (par défaut à 0 si non spécifié).'
        ),
        new OA\Property(
            property: 'description',
            type: 'string',
            nullable: true,
            example: 'Écran 4K, 16GB RAM, SSD 512GB.',
            description: 'Description détaillée du produit.'
        ),
        new OA\Property(
            property: 'category',
            type: 'integer',
            example: 1,
            description: 'ID de la catégorie à laquelle appartient le produit (ID existant requis).'
        ),
    ],
    required:['name', 'price', 'category']
)]
final class ProductSchema
{
    //classe vide qui sert de conteneur pour l'attribut OA\Schema
}