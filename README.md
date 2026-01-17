# GEO Authority Suite

![WordPress Plugin](https://img.shields.io/badge/WordPress-Plugin-blue)
![Version](https://img.shields.io/badge/version-1.0.0-green)
![License](https://img.shields.io/badge/license-GPL2%2B-orange)

> **Plugin WordPress pour optimiser votre visibilité dans les moteurs d'IA générative (ChatGPT, Claude, Perplexity, etc.)**

## 🎯 Objectif

**GEO Authority Suite** centralise toutes les fonctionnalités nécessaires au **GEO (Generative Engine Optimization)** : structuration des entités, génération de JSON-LD Schema.org, création du fichier `llms.txt`, et audits de contenu.

### Fonctionnalités principales

- 🏢 **Gestion des entités** : Personnes, Organisations, Produits, Services, Lieux, Événements
- 📊 **Génération JSON-LD** : Schema.org optimisé pour l'indexation par les IA
- 📄 **Fichier llms.txt** : Index standardisé pour les moteurs d'IA générative
- 🔍 **Audits automatiques** : Vérification de la cohérence des entités et du contenu
- 🔗 **Shortcode [entity]** : Mention sémantique des entités dans vos articles

---

## 🔒 Confidentialité

**Aucune donnée n'est transmise par ce plugin à des services externes.**  
Les informations exposées sont strictement celles que vous choisissez de rendre publiques via vos contenus WordPress.

---

## 🚀 Installation

1. Téléchargez le plugin depuis ce dépôt
2. Uploadez le dossier dans `/wp-content/plugins/`
3. Activez le plugin depuis **Extensions > Extensions installées**
4. Accédez au menu **Entités** dans votre admin WordPress

---

## 📖 Guide d'utilisation

### 1️⃣ Créer une Organization principale

Votre site doit avoir **une seule Organisation principale** qui représente votre entreprise, association ou projet.

1. Aller dans **Entités > Ajouter**
2. **Titre** : Le nom de votre entreprise/site
3. **Type** : `Organization`
4. Remplir les champs :
   - **URL** : Votre site web
   - **Description** : Présentation de votre organisation
   - **Logo** : Image à la une ou URL du logo
   - **Adresse** : Coordonnées postales (si applicable)
5. **sameAs** : Ajouter vos liens sociaux (un par ligne)
   ```
   https://facebook.com/votre-page
   https://twitter.com/votre-compte
   https://linkedin.com/company/votre-entreprise
   ```

### 2️⃣ Créer les Person (auteurs, employés)

Chaque personne mentionnée sur votre site devrait avoir une entité dédiée.

1. **Entités > Ajouter**
2. **Type** : `Person`
3. Remplir :
   - **Fonction** (jobTitle)
   - **Email**
   - **Téléphone**
   - **Photo** (image à la une)
4. **⚠️ Important** : Dans **Relations**, sélectionner votre Organization dans **"Travaille pour (worksFor)"**

### 3️⃣ Mentionner les entités dans vos articles

Utilisez le shortcode `[entity id=X]` pour créer des mentions sémantiques :

```markdown
J'ai rencontré [entity id=5] lors de la conférence organisée par [entity id=3].
```

#### EXEMPLES D'UTILISATION

1. MENTION INLINE SIMPLE
```markdown
[entity id=5]
```
→ Affiche : "Erwan Tanguy" (lien simple)

2. MENTION AVEC FONCTION
```markdown
[entity id=5 show="name+title"]
```
→ Affiche : "Erwan Tanguy (CEO, développeur)"

3. MENTION COMPLÈTE
```markdown
[entity id=5 show="full"]
```
→ Affiche : "Erwan Tanguy – CEO – Expert en SEO depuis..."

4. SANS LIEN
```markdown
[entity id=5 show="name+title" link="no"]
```
→ Affiche : "Erwan Tanguy (CEO)" (pas de lien)

5. AVEC IMAGE MINIATURE
```markdown
[entity id=5 image="yes" show="name+title"]
```
→ Affiche : [photo] Erwan Tanguy (CEO)

6. CARTE ENRICHIE
```markdown
[entity id=5 display="card"]
```
→ Affiche : Carte complète avec photo, nom, fonction, description, bouton

7. TOOLTIP AU SURVOL
```markdown
[entity id=5 display="tooltip"]
```
→ Affiche : Lien avec info-bulle affichant fonction + description

*/


Le shortcode génère automatiquement :
- Un lien vers la page de l'entité (si URL définie)
- Une référence dans le graphe d'entités JSON-LD
- Un attribut `data-entity-id` pour le tracking

---

## ✅ Vérifier le JSON-LD généré

### Méthode 1 : Code source
1. Afficher le code source de votre page (`Ctrl+U` ou `Cmd+U`)
2. Rechercher `<script type="application/ld+json">`
3. Vérifier la présence de vos entités dans le graphe `@graph`

### Méthode 2 : Validateurs en ligne
- [Schema.org Validator](https://validator.schema.org/)
- [Google Rich Results Test](https://search.google.com/test/rich-results)

Copier-coller le JSON-LD extrait pour validation.

---

## 🎓 Bonnes pratiques GEO

| Règle | Description |
|-------|-------------|
| **1 seule Organization** | Évitez les doublons, une seule Organisation principale par site |
| **Relier les Person** | Toutes les personnes doivent être liées via `worksFor` ou `memberOf` |
| **Photos obligatoires** | Ajoutez des images pour chaque entité (logo, portrait) |
| **Descriptions riches** | Rédigez des descriptions complètes et informatives |
| **Liens sociaux** | Remplissez `sameAs` avec tous vos profils (Facebook, LinkedIn, Twitter) |
| **Utiliser [entity]** | Mentionnez vos entités dans les articles avec le shortcode |

---

## 📦 Types d'entités disponibles

| Type | Usage |
|------|-------|
| **Organization** | Votre entreprise, association, site web |
| **Person** | Auteurs, employés, experts, partenaires |
| **LocalBusiness** | Entreprise avec adresse physique (restaurant, magasin) |
| **Product** | Produits que vous vendez ou présentez |
| **Service** | Services que vous proposez |
| **Place** | Lieux géographiques (ville, monument, bureau) |
| **Event** | Événements, conférences, webinars |

---

## 🔍 Audits automatiques

### Audit des entités
**Menu : Entités > Audit Entités**

Vérifie :
- ✅ Présence d'une Organization principale
- ✅ Unicité des `@id` (identifiants sémantiques)
- ✅ Cohérence des relations (`worksFor`, `memberOf`)
- ✅ Complétude des métadonnées (URL, description, logo)

### Audit du contenu
**Menu : Entités > Audit Contenu**

Analyse vos articles et calcule un **Score GEO** basé sur :
- FAQ structurées (`<details>` ou H3+P)
- Citations (`<blockquote>`)
- Images avec attributs alt
- Vidéos et audios
- Mentions d'entités via `[entity]`

**Score GEO** :
- 🟢 **≥ 80** : Excellent (optimisé pour les IA)
- 🟡 **50-79** : Bon (peut être amélioré)
- 🔴 **< 50** : À améliorer (manque d'éléments structurés)

---

## 📄 Fichier llms.txt

**Menu : Entités > llms.txt**

Générez automatiquement un fichier `llms.txt` à la racine de votre site, indexant :
- Informations du site (nom, description, contact)
- Réseaux sociaux
- Articles récents avec résumés
- Pages principales
- Entités référencées

### Options disponibles
- ✅ **Génération automatique** lors de la publication d'articles
- ✅ **Lien dans le `<head>`** : `<link rel="llms" href="/llms.txt">`
- 🔢 **Nombre d'articles** à inclure (5-100)

---

## 🛠️ Compatibilité

- **WordPress** : 5.8 ou supérieur
- **PHP** : 7.4 ou supérieur
- **Éditeur** : Gutenberg (Blocs) ou Classique

### Plugins compatibles
- ✅ Yoast SEO
- ✅ Rank Math
- ✅ All in One SEO
- ✅ MediaGEO (détection automatique des médias)

---

## 📚 Ressources

- [Schema.org Documentation](https://schema.org/)
- [Schema.org Validator](https://validator.schema.org/)
- [Google Rich Results Test](https://search.google.com/test/rich-results)
- [llms.txt Standard](https://llmstxt.org/)
- [Creative Commons Licenses](https://creativecommons.org/licenses/)

---

## 👨‍💻 Auteur

**Erwan Tanguy - Ticoët**  
🌐 [ticoet.fr](https://www.ticoet.fr/)

---

## 📝 Licence

GPL2+  
Ce plugin est distribué sous licence GNU General Public License v2 ou ultérieure.

---

## 🤝 Contribution

Les contributions sont les bienvenues ! Pour proposer une amélioration :

1. Forkez le projet
2. Créez une branche (`git checkout -b feature/amelioration`)
3. Committez vos modifications (`git commit -m 'Ajout fonctionnalité X'`)
4. Pushez vers la branche (`git push origin feature/amelioration`)
5. Ouvrez une Pull Request

---

## 🐛 Support

Pour signaler un bug ou demander une fonctionnalité :
- Ouvrez une [Issue](../../issues) sur GitHub
- Contactez l'auteur via [ticoet.fr](https://www.ticoet.fr/)

---

## 📊 Changelog

### Version 1.0.0
- ✨ Première version stable
- 🏢 Gestion complète des entités Schema.org
- 📄 Génération du fichier llms.txt
- 🔍 Audits entités et contenu
- 🔗 Shortcode [entity] pour mentions sémantiques
