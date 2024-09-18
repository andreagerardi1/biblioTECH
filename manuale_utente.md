# Manuale utente
## Prerequisiti
Lista dei software necessari per eseguiure l'applicazione
- `PostgreSQL`
- `PHP`
- `Web server`, nel mio caso web server interno di PHP

## Come avviare l'applicazione
Nel file `.zip` fornito si trovano sia il **dump sql** del database, sia  
i file `.php` che contengono il contenuto delle pagine. 
Per eseguire l'applicazione:
1. Importare il file di **dump** nel propiro database:
    ```
    # comando da terminale linux
    psql nome_database < dump.sql
    ```
    dove "nome_database" è da sostituire con il nome del proprio database
2. Creare la **connessione** tra `PHP` e `PostgreSQL` creando il seguente file  
    da denominare `connection.php` nella cartella `webapp`:
    ```
    <?php
    $db = pg_connect("host=localhost port=5432 dbname=biblioteca user=postgres password=propria_password");
    ?>
    ```
    dove "propria_password" è da sostituire con la propia password per l'utente "postgres".  
    
3. Avviare il proprio `webserver`. Per il testing, sarà sufficiente:
    ```
    # comando da terminale linux
    cd webapp
    php -S localhost:8000
    ```

4. Recarsi all'**indirizzo** del server. Nel caso del webserver php:
    `localhost:8000`  

Dopo questi passaggi, verrà servita la pagina `index.php`, da cui iniziare la navigazione.

## Utenti utilizzabili
Per testare il corretto funzionamento sono forniti due utente, uno per il lettore e  
uno per il bibliotecario:
- `utente lettore`:
    email: laura@bianchi.it  
    password: laura123!  
- `utente bibliotecario`:
    email: biblio@tecario.it  
    password: biblio123!