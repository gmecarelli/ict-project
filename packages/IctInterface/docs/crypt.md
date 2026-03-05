# Ricerca su campi crittati — Soluzione con campo _hash

## Il problema

I campi di tipo `crypted` usano `Crypt::encryptString()` (AES-256-CBC con IV random):
- Lo stesso testo cifrato due volte produce ciphertext diversi
- SQL LIKE è impossibile sui dati cifrati
- Anche il match esatto è impossibile (IV diverso ogni volta)

## Soluzione implementata: Blind Index con HMAC-SHA256

Per ogni campo crittato `campo`, viene affiancato un campo `campo_hash` contenente un hash deterministico (HMAC-SHA256) del valore normalizzato. La ricerca avviene su `_hash` con match esatto; la visualizzazione usa `campo` (decrittato con `Crypt::decryptString`).

### Flusso dati

```
SALVATAGGIO:
  valore utente "Mario Rossi"
  → campo       = Crypt::encryptString("Mario Rossi")     // per display/edit
  → campo_hash  = hmac_sha256(normalize("Mario Rossi"))   // per ricerca
                 = hmac_sha256("mariorossi")

RICERCA (filtro):
  input utente "mario rossi"
  → normalize("mario rossi") = "mariorossi"
  → hmac_sha256("mariorossi")
  → WHERE campo_hash = 'hash_calcolato'

DISPLAY:
  → campo viene decrittato con Crypt::decryptString() (invariato)
  → il filtro mostra il testo originale inserito dall'utente (invariato)
```

### Normalizzazione

La funzione `_normalize()` rimuove virgolette, apici, spazi e backtick, e converte in minuscolo. Questo permette di trovare match indipendentemente dalla formattazione:

```php
_normalize("Mario Rossi")  → "mariorossi"
_normalize("mario rossi")  → "mariorossi"
_normalize("MARIO ROSSI")  → "mariorossi"
```

### Helper disponibili

```php
_normalize($val)      // Normalizza il valore (lowercase, rimuove spazi/apici)
_encryptHash($val)    // Genera HMAC-SHA256 del valore normalizzato usando app.key
```

## Prerequisiti: migration

Per ogni tabella con campi crittati, aggiungere la colonna `_hash` nella migration:

```php
// Esempio: se esiste $table->text('nome') (crittato)
$table->string('nome_hash', 64)->nullable()->index();
```

La colonna è `string(64)` perché HMAC-SHA256 produce sempre 64 caratteri hex. L'indice rende la ricerca performante anche su 1M+ record.

## Migrazione dati esistenti

Se ci sono dati già crittati nel DB, serve uno script una tantum per popolare i campi `_hash`:

```php
// Per ogni record con campo crittato:
$record->nome_hash = _encryptHash(_decrypt($record->nome));
$record->save();
```

## File modificati

| File | Modifica |
|------|----------|
| `helpers.php` | Aggiunte funzioni `_normalize()` e `_encryptHash()` |
| `EditableFormComponent.php` | Scrittura `campo_hash` al salvataggio |
| `ModalFormComponent.php` | Scrittura `campo_hash` al salvataggio |
| `ChildFormComponent.php` | Scrittura `campo_hash` al salvataggio |
| `ReportService.php` | Filtro su `campo_hash` invece che su campo crittato |

## Limitazioni

- **Solo match esatto** (case-insensitive grazie alla normalizzazione), non supporta LIKE/pattern matching
- La ricerca LIKE su campi crittati resta impossibile senza decifrare ogni riga
- Se si cambia `app.key`, tutti gli hash devono essere rigenerati

## Riepilogo opzioni valutate

| Approccio | LIKE | Match esatto | Performance 1M+ | Complessità |
|-----------|------|-------------|-----------------|-------------|
| `Crypt::encryptString` (precedente) | No | No | — | — |
| **Blind index HMAC-SHA256 (attuale)** | No | **Sì** | **Ottima (index)** | **Bassa** |
| MySQL `AES_ENCRYPT/DECRYPT` | Sì | Sì | Buona (full scan) | Media |
