# fotoclubperspectief

Deze plugin heeft de volgende features:

## child theme voor de twenty twenty theme

## custom home page:
  - rij 1:  1/3 Mededelingen | 2/3 uitgelicht afbeelding met onderschrift   
  - rij 2:  1/3 Custom text 1 | 1/3 Custom text 2 | 1/3 Agenda
  - rij 3:  Over volle breedte 4 cards: PORTRET, NATUUR, STRAAT, ARCHITECTUUR

## Ledenlijst

In de backend moet een ledenlijst bijgehouden kunnen worden met de volgende velden:

- Voornaam - string
- Achternaam - string
- Lidnr fotobond - string
- Bar - boolean
- adres - string
- Postcode - string
- Plaats - string
- Telefoon - string
- Email - string
- Bestuur - boolean
- Programma cie - boolean
- Tentoonstelling cie - boolean
- Wedstrijden cie - boolean
- Archief foto cie - boolean
- Website cie - boolean
- Redactie cie - boolean
- natuur werkgroep - boolean
- portret werkgroep - boolean
- straat werkgroep - boolean
- architectuur werkgroep - boolean
- laptop bediening - boolean


## AGENDA

Velden:
- Datum
- Beschrijving: bullet list
- Avondleiding: select 1 Lid van ledenlijst (voornaam)
- Bardienst: select 2 x Lid van ledenlijst (voornaam)
- Laptop: select 1 Lid van ledenlijst (voornaam)
- Clubavond: boolean

In de backend moet de redacteur de agenda kunnen vullen

Op de homepage worden de komende 4 items getoond met alleen de datum en de beschrijving. Afhankelijk van het type krijgt de datum een andere achtergrondkleur.

## HOMEPAGE

In de backend moet de redacteur kunnen inrichten:

- Uitgelicht afbeelding met onderschrift
- Custom text 1: kop met text en optioneel afbeelding
- Custom text 2: kop met text en optioneel afbeelding
- Mededelingen: 1 of meerdere sets van kop met text

## Installatie (ontwikkeling)

1. Kopieer `wp-content/plugins/fotoclubperspectief` naar de `wp-content/plugins`-map van je WordPress-installatie en activeer **Fotoclub Perspectief** onder Plugins.
2. Kopieer `wp-content/themes/fotoclubperspectief-child` naar `wp-content/themes` en activeer het child theme **Fotoclub Perspectief**. Het parent theme **Twenty Twenty** moet geïnstalleerd zijn.
3. Ga naar **Instellingen → Lezen** en stel een statische pagina in als homepage; kies een pagina die je als startpagina wilt gebruiken (het child theme gebruikt `front-page.php` voor de layout).
4. Homepage-inhoud: **Instellingen → Fotoclub homepage** (uitgelicht, custom teksten, mededelingen; per homepage-card een URL en optioneel een afbeelding).
5. Ledenlijst op een pagina: voeg de shortcode `[fcp_ledenlijst]` toe. Optioneel: `[fcp_ledenlijst show_contact="0"]` om telefoon en e-mail te verbergen.

## Ledenlijst vullen (backend)

1. In het WordPress-dashboard: menu **Leden** (eigen invoer voor het custom post type `fcp_member`).
2. Kies **Nieuw lid toevoegen** of open een bestaand lid om te bewerken.
3. Vul het formulier **Gegevens lid** in: voornaam, achternaam, lidnummer fotobond, adresgegevens, telefoon, e-mail, en de gewenste vinkjes bij commissies en werkgroepen.
4. Sla op. De titel van het bericht wordt automatisch opgebouwd uit **voornaam** en **achternaam** (handig voor de lijstweergave in het admin).
5. **Sortering** in het overzicht: je kunt op de kolom **Achternaam** sorteren.
6. **Agenda-koppeling:** avondleiding, bardienst en laptop in agenda-items zijn dropdowns op basis van deze leden. Zorg dat leden **eerst** in de ledenlijst staan voordat je ze in de agenda kunt kiezen (er wordt op **voornaam** getoond in de selecties).

**Publiek:** op een gewone pagina de shortcode `[fcp_ledenlijst]` plaatsen. Met `[fcp_ledenlijst show_contact="0"]` worden de kolommen telefoon en e-mail niet getoond.

## Agenda vullen (backend)

1. In het dashboard: menu **Agenda**.
2. Kies **Agenda-item toevoegen** of bewerk een bestaand item.
3. Vul in:
   - **Datum** — kalenderdatum van de activiteit.
   - **Beschrijving** — tekst met de ingebouwde editor; gebruik waar nodig een opsomming (lijst).
   - **Avondleiding** — één lid uit de ledenlijst (dropdown op voornaam).
   - **Bardienst** — twee keuzes (twee personen); laat leeg als er niemand is ingeroosterd.
   - **Laptop** — één lid.
   - **Clubavond** — vink aan als het een clubavond betreft.
4. Sla op. De titel van het agenda-item wordt in de admin afgeleid van de gekozen datum (ter referentie).

**Homepage:** in het homepage-blok *Agenda* worden automatisch de **komende vier** items getoond (vanaf vandaag, oplopend op datum). Alleen **datum** en **beschrijving** zijn zichtbaar voor bezoekers. Als **Clubavond** aan staat, krijgt de datum een andere achtergrondkleur dan bij een gewone activiteit.

### extra css theme

```css

.home .entry-header {
   display: none;
}
.entry-header {
   display: none;
}

.wp-block-columns a {
	text-decoration: none;
}
/*
 * oud: https://fotoclubperspectief.nl/wp-content/uploads/Logo-FC-Perspectief-Marjon-groot--300x84.jpg
 *   * nieuw: https://fotoclubperspectief.nl/wp-content/uploads/FOTOCLUBLOGO300px.png
 * 
 */
.header-titles-wrapper {
    height: 100px;
	 
    background-image: url(https://fotoclubperspectief.nl/wp-content/uploads/FOTOCLUBLOGO300px-wit.png);
    background-repeat: no-repeat;
	background-position:left;
}

.header-titles {
    text-indent: -9999px;
    width: 320px;
    height: 100px;
    white-space: nowrap;
}

@media ( max-width: 479px ) {
	.header-titles-wrapper {
    background-image:url(https://fotoclubperspectief.nl/wp-content/uploads/FOTOCLUBLOGO200px-wit.png);
	}

}

/*	.entry-content p {
		font-family: "Inter var", -apple-system, BlinkMacSystemFont, "Helvetica Neue", Helvetica, sans-serif;
	}
*/

.hkpadding {
	padding: 0 0.5em 0 0.5em;
}

.wp-block-column {
	padding: 3rem !important;
}

.home-block-padding {
	padding: 1rem !important;
}

/* menu sepatators | */
.primary-menu .icon {
	display: none;
}

.primary-menu li:not(:last-child) {
  border-right: 2px solid #ffffff;
}

.primary-menu li {
	padding-left: 2rem;
  padding-right: 2rem;
	margin: 0;
}

.primary-menu > li.menu-item-has-children > a {
    padding-right: 0px;
}

/*
 * accentkeur hetzelfde als header footer */
button, .button, .faux-button, .wp-block-button__link, .wp-block-file .wp-block-file__button, input[type="button"], input[type="reset"], input[type="submit"], .bg-accent, .bg-accent-hover:hover, .bg-accent-hover:focus, :root .has-accent-background-color, .comment-reply-link {
background-color: #008b8b;
}

.color-accent, .color-accent-hover:hover, .color-accent-hover:focus, :root .has-accent-color, .has-drop-cap:not(:focus)::first-letter, .wp-block-button.is-style-outline, a {
	color: #e22658;
}

/*
 * color: #008b8b;
 */

/*
 * menu witte letters */
.primary-menu ul a {
    color: white;
}

.primary-menu > li > a {
    color: #fff !important;
}

body:not(.overlay-header) .primary-menu > li > a, body:not(.overlay-header) .primary-menu > li > .icon, .modal-menu a, .footer-menu a, .footer-widgets a, #site-footer .wp-block-button.is-style-outline, .wp-block-pullquote:before, .singular:not(.overlay-header) .entry-header a, .archive-header a, .header-footer-group .color-accent, .header-footer-group .color-accent-hover:hover {
    color: #ffffff;
}
/*
 * lege ruimte boven in body
 * */
.post-inner {
    padding-top: 3rem;
}

/*
 * blog post navigatie onderaan
 */
.pagination-single hr {
    border: 3px solid #008b8b;
}

mark {
	    background-color: inherit;
		padding: 5px;
}


.wfu_container {
	margin-left: auto !important;
	margin-right: auto !important;
}

.wp-block-columns.no-gap {
  gap: 0;
}

.wp-block-columns.no-gap .wp-block-column {
  padding: 0;
}

.entry-content > .wp-block-columns.stacked-columns {
  margin-bottom: 0 !important;
}

.entry-content > .wp-block-columns.stacked-columns + .wp-block-columns.stacked-columns {
  margin-top: 0 !important;
}
```

### site icon
https://fotoclubperspectief.nl/wp-content/uploads/favicon-perspectief.jpg


### kleuren
achtergrond #ffffff NIET NODIG

header footer #008b8b

### thema opties
alleen samenvatting aangevinkt

### cover template
overlay #ffffff
overlay tekst #000000

### instellingen
lezen selecteer nieuwe homepage pagina
