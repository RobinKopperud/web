# Modernisering av web-appene

Denne leveransen moderniserer alle appene i repoet med unntak av `prissammenligning/`. Endringene er samlet rundt tre prioriterte forbedringer per app.

## Utført

### Portal

1. Søkbar og responsiv appoversikt.
2. Tydelige beskrivelser og kategorier som forklarer hva hver app gjør.
3. «Sist brukt» i nettleseren for raskere vei tilbake.

### Boligavkastning

1. Hurtigscenarioer for forsiktig, balansert og optimistisk prisutvikling.
2. Lagre, hente, slette og nullstille egne scenarioer lokalt i nettleseren.
3. Forklarende resultatvurdering og kopierbart sammendrag.

### Borettslag

1. Ny tjenesteoversikt med status, innloggingsnivå og hurtigvalg.
2. Søk og fraksjonsfilter for avfallsstasjoner.
3. Responsivt oversiktskart, klare fraksjonsmerker og lenke til veibeskrivelse.

### Kryptooversikt

1. Investeringsjournal med kjøpsdato, strategi og notat lagret i databasen.
2. Porteføljefordeling basert på markedsverdi og tidspunkt for siste prisoppdatering.
3. Søk i posisjoner, konsekvent norsk språk og tydeligere kjøps-/salgsflyt.

### Mine verdier

1. Kategorifordeling av nettoformuen.
2. Søk, kategori- og sorteringskontroller for eiendelslisten.
3. Daglige verdisnapshots i databasen og endring siden forrige verdsetting.

### Enkelparkering

1. Statuskort for ledige plasser, ladere og egen køstatus.
2. Kombinert søk og filtre for ledig plass/lader.
3. Visuell køfremdrift, forklaring av neste steg og et nytt responsivt designsystem for bruker- og adminsider.

### Treningslogg

1. Sikker opplasting av JPG, PNG eller WebP, maks 5 MB.
2. Maks ett bilde per bruker og dato håndheves av en unik databaseindeks.
3. Bildegalleri på oversikten, miniatyr i historikken og opprydding av bildefil når registreringen slettes.

## Databasemigreringer som må kjøres

- `crypto-tracker/migrations/2026-09-08_add_order_journal.sql`
- `eiendeler/migrations/2026-09-08_add_asset_valuations.sql`
- `treningslogg/migrations/2026-09-08_add_entry_images.sql`

Ta databasebackup før migrering. Webserveren må ha skrivetilgang til `treningslogg/uploads/`. Mappen er sperret for direkte nett-tilgang; bilder leveres gjennom en innloggingsbeskyttet PHP-rute.

## Anbefalte neste datafelter

Dette er forslag som ikke er lagt til ennå, slik at neste databaserevisjon kan prioriteres bevisst.

| App | Data som bør lagres | Nytte |
| --- | --- | --- |
| Boligavkastning | Brukertilknyttede scenarioer, renteantakelser og scenario-versjoner | Samme scenario på flere enheter og sporbar sammenligning over tid |
| Borettslag | Avfallsstasjoner, koordinater, fraksjoner, avvik og tømmedatoer | Admin kan oppdatere reell informasjon uten kodeendring |
| Krypto | Målallokering, pris-snapshots og skattemessig kostgrunnlag | Rebalanseringsvarsel, historisk graf og bedre rapportering |
| Mine verdier | Dokumenter, forsikring/utløpsdato og ekstern verdikilde | Samlet dokumentasjon og påminnelser om fornyelse |
| Enkelparkering | Varslingspreferanser, registreringsnummer, hendelseslogg og samtykker | Bedre varsling, revisjonsspor og enklere administrasjon |
| Treningslogg | Enhet per måling, personlige mål, bildeforklaring og personvernstatus | Flere måletyper, måloppfølging og tydelig kontroll på sensitive bilder |

## Anbefalt videre arbeid

1. Legg CSRF-token på alle POST-skjemaer og innfør felles sikkerhetsheadere.
2. Flytt felles innlogging, navigasjon og designsystem til gjenbrukbare PHP-komponenter.
3. Legg til ende-til-ende-tester mot en separat testdatabase før automatisk produksjonsutrulling.
