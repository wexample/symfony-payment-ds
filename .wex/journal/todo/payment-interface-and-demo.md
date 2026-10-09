# Paiement : l'interface (-ds) et la démo (-demo)

Opened: 2026-10-09
Author: agent:main

Une seule todo pour `symfony-payment-ds` et `symfony-payment-demo`, vides à ce jour. Le backend est fait et publié : ne pas le refaire, s'en servir, et le compléter là où l'interface en a besoin.

## À lire d'abord (plutôt que de le répéter ici)

- `symfony-payment/.wex/knowledge/usage/overview.md.j2` : `PaymentService` (create, initiate, synchronize, cancel, confirmManualPayment, refund…), statuts, événements, accès (`PaymentAccessService`), routes `/_payment/status/{id}` et `/_payment/webhook/{provider}`.
- `symfony-remote-payment` (knowledge + `src/Interface`) : passerelles, `PaymentInitiation` (redirection ou instructions), `FakePaymentProvider` pour la démo ; `symfony-remote-payment-stripe` pour le vrai prestataire.
- `symfony-tunnels/.wex/knowledge/usage/overview.md.j2`, et `symfony-tunnels-demo` comme exemple de tunnel sur le design system.
- `symfony-cart/.wex/journal/todo/extract-from-network.md` (section Status) : `CartCheckoutService` passe déjà par symfony-payment ; le tunnel d'achat reste à faire.
- La proposition d'origine : `NETWORK/archeo/proposed-packages/done/symfony-payment/todo/extract-from-network.md` (lecture seule, données de prod côté network : ne rien y exécuter).
- `symfony-accounting/.wex/journal/todo/5a40fb66c266.md` : règles du propriétaire et pièges de MOJOE.

## Ce que -ds devrait fournir (à discuter avec le propriétaire avant de coder)

- **Une étape de paiement réutilisable** : choix du moyen quand il y en a plusieurs, puis redirection vers le prestataire ou instructions (manuel, virement). Pensée pour être une étape d'un tunnel symfony-tunnels, utilisable aussi seule.
- **La page de retour** : attend le statut (`/_payment/status/{id}`, ou Mercure via symfony-live s'il est là), puis succès, échec avec possibilité de réessayer, ou annulation.
- **« Mes paiements »** : historique de l'utilisateur, détail, reçu.
- **Administration** : liste filtrable par statut ou prestataire, détail, remboursement total ou partiel, confirmation d'un paiement manuel, synchronisation avec le prestataire. Formulaires symfony-forms, rôle configurable comme les écrans d'administration de symfony-user-ds.

Le tunnel d'achat complet (panier, adresse, paiement, confirmation) relève plutôt d'un futur `symfony-cart-ds`, qui réutiliserait l'étape de paiement : à confirmer avec le propriétaire.

## La démo

- Sur les comptes de `symfony-user-demo` ; pas d'utilisateurs propres.
- `FakePaymentProvider` : aucun compte Stripe nécessaire. Montrer un paiement réussi, un échec, une annulation, un paiement manuel confirmé par l'admin (`demo-admin`) et un remboursement.
- Un petit objet payable de démo (`PayableInterface`) si cart n'est pas installé.
- Rédigée en anglais ; `.trans.yml` en `en:` seulement.

## Règles

- Seulement les composants du design system (pas de HTML brut ni de CSS propre) ; actions de page en `page_title_actions`, modales et panneaux du loader.
- À vérifier dans MOJOE design system (`MOJOE/local/design-system`), sans commiter ce dépôt : il appartient au propriétaire.
- Les parcours multi-acteurs (payeur, admin) peuvent devenir des scénarios symfony-testing (`symfony-testing/.wex/knowledge/usage/scenarios.md.j2`), comme `tests/Scenario/` de MOJOE design system.
