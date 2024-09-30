--
-- PostgreSQL database dump
--

-- Dumped from database version 16.3
-- Dumped by pg_dump version 16.3

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: biblioteca_ag; Type: SCHEMA; Schema: -; Owner: pg_database_owner
--

CREATE SCHEMA biblioteca_ag;


ALTER SCHEMA biblioteca_ag OWNER TO pg_database_owner;

--
-- Name: SCHEMA biblioteca_ag; Type: COMMENT; Schema: -; Owner: pg_database_owner
--

COMMENT ON SCHEMA biblioteca_ag IS 'standard public schema';


--
-- Name: aggiorna_disponibilita_funzione(); Type: FUNCTION; Schema: biblioteca_ag; Owner: andrea
--

CREATE FUNCTION biblioteca_ag.aggiorna_disponibilita_funzione() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
    begin
        set search_path to "biblioteca_ag";
        update copia
        set stato = 'non disponibile'
        where codice = new.copia_codice;
        return new;
    end;
    $$;


ALTER FUNCTION biblioteca_ag.aggiorna_disponibilita_funzione() OWNER TO andrea;

--
-- Name: aggiorna_ritardi_funzione(); Type: FUNCTION; Schema: biblioteca_ag; Owner: andrea
--

CREATE FUNCTION biblioteca_ag.aggiorna_ritardi_funzione() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
    begin
        set search_path to "biblioteca_ag";
        if (new.restituzione > new.fine_concessione) then
            raise info 'Restituzione avvenuta in ritardo';
            update lettore
            set volumi_in_ritardo = volumi_in_ritardo +1
            where cf = new.lettore_cf;
        end if;
        return new;
    end;
    $$;


ALTER FUNCTION biblioteca_ag.aggiorna_ritardi_funzione() OWNER TO andrea;

--
-- Name: blocco_presititi_funzione(); Type: FUNCTION; Schema: biblioteca_ag; Owner: andrea
--

CREATE FUNCTION biblioteca_ag.blocco_presititi_funzione() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
        declare ritardi integer;
        begin
            set search_path to "biblioteca_ag";
            select lettore.volumi_in_ritardo into ritardi
            from lettore
            where lettore.cf = new.lettore_cf;

            if ritardi > 4 then
                raise exception 'ritardi massimi raggiunti!';
            else
                return new;
            end if;
        end;
    $$;


ALTER FUNCTION biblioteca_ag.blocco_presititi_funzione() OWNER TO andrea;

--
-- Name: check_proroga_funzione(); Type: FUNCTION; Schema: biblioteca_ag; Owner: andrea
--

CREATE FUNCTION biblioteca_ag.check_proroga_funzione() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
    begin
        set search_path to "biblioteca_ag";
        if (old.fine_concessione < current_date) then
            raise exception 'Impossibile prorogare prestito causa ritardo';
        end if;
        return new;
    end;
    $$;


ALTER FUNCTION biblioteca_ag.check_proroga_funzione() OWNER TO andrea;

--
-- Name: controlla_disponibilita_funzione(); Type: FUNCTION; Schema: biblioteca_ag; Owner: andrea
--

CREATE FUNCTION biblioteca_ag.controlla_disponibilita_funzione() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
        begin
            set search_path to "biblioteca_ag";
            if ((select count(*)
                 from copia
                 where libro_isbn = new.libro_isbn
                 and stato = 'disponibile') = 0) then
                raise exception 'Libro non disponibile';
            end if;
            return new;
        end;
    $$;


ALTER FUNCTION biblioteca_ag.controlla_disponibilita_funzione() OWNER TO andrea;

--
-- Name: prolunga_prestito(bigint); Type: PROCEDURE; Schema: biblioteca_ag; Owner: andrea
--

CREATE PROCEDURE biblioteca_ag.prolunga_prestito(IN id_prestito bigint)
    LANGUAGE plpgsql
    AS $$
    begin
        set search_path to "biblioteca_ag";
        if (current_date > (select p.fine_concessione
             from prestito p
             where p.id = id_prestito
             )) then
                raise exception 'impossibile prolungare il prestito perché già in ritardo';
        end if;
        update prestito set fine_concessione = fine_concessione + 30 where id = id_prestito;
    end;
    $$;


ALTER PROCEDURE biblioteca_ag.prolunga_prestito(IN id_prestito bigint) OWNER TO andrea;

--
-- Name: rendi_disponibile_function(); Type: FUNCTION; Schema: biblioteca_ag; Owner: andrea
--

CREATE FUNCTION biblioteca_ag.rendi_disponibile_function() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
    begin
        set search_path to "biblioteca_ag";
        update copia
        set stato = 'disponibile'
        where codice = new.copia_codice;
        return new;
    end;
    $$;


ALTER FUNCTION biblioteca_ag.rendi_disponibile_function() OWNER TO andrea;

--
-- Name: richiedi_prestito_isbn(numeric, character, bigint); Type: PROCEDURE; Schema: biblioteca_ag; Owner: andrea
--

CREATE PROCEDURE biblioteca_ag.richiedi_prestito_isbn(IN isbn numeric, IN cf character, IN sede bigint)
    LANGUAGE plpgsql
    AS $$
    declare copia_in_sede bigint;
    begin
        set search_path to "biblioteca_ag";
        if (sede is not null) then
            copia_in_sede = (select codice
            from copia
            where libro_isbn = isbn and stato = 'disponibile' and sede_cod = sede
            limit 1);
            if found then
                insert into prestito (copia_codice, libro_isbn, lettore_cf) values (copia_in_sede,
                                                                                    isbn,cf);
                return;
            end if;
            raise info 'libro non presente nella sede, verrà cercato altrove';
        end if;
        copia_in_sede = (select codice
        from copia
        where libro_isbn = isbn and stato = 'disponibile'
        limit 1);
        insert into prestito (copia_codice, libro_isbn, lettore_cf) values (copia_in_sede, isbn, cf);
    end
    $$;


ALTER PROCEDURE biblioteca_ag.richiedi_prestito_isbn(IN isbn numeric, IN cf character, IN sede bigint) OWNER TO andrea;

--
-- Name: tetto_prestiti_funzione(); Type: FUNCTION; Schema: biblioteca_ag; Owner: andrea
--

CREATE FUNCTION biblioteca_ag.tetto_prestiti_funzione() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
    declare tetto integer;
    begin
        set search_path to "biblioteca_ag";
        if ((select lettore.categoria
             from lettore
             where lettore.cf = new.lettore_cf) = 'base') then
            tetto = 3;
        else
            tetto = 5;
        end if;
        if ((select count(*)
             from prestito
             where prestito.lettore_cf = new.lettore_cf
             and prestito.restituzione is null) >= tetto) then
            raise exception 'Prestito non concesso: numero massimo di prestiti raggiunto!';
        else
            return new;
        end if;
    end;
    $$;


ALTER FUNCTION biblioteca_ag.tetto_prestiti_funzione() OWNER TO andrea;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: autore; Type: TABLE; Schema: biblioteca_ag; Owner: andrea
--

CREATE TABLE biblioteca_ag.autore (
    id bigint NOT NULL,
    nome character varying(255) NOT NULL,
    cognome character varying(255) NOT NULL,
    data_nascita date NOT NULL,
    data_morte date,
    biografia text,
    CONSTRAINT anagrafico CHECK (((data_nascita < data_morte) AND (data_nascita < CURRENT_DATE) AND (data_morte <= CURRENT_DATE)))
);


ALTER TABLE biblioteca_ag.autore OWNER TO andrea;

--
-- Name: autore_id_seq; Type: SEQUENCE; Schema: biblioteca_ag; Owner: andrea
--

ALTER TABLE biblioteca_ag.autore ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME biblioteca_ag.autore_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: copia; Type: TABLE; Schema: biblioteca_ag; Owner: andrea
--

CREATE TABLE biblioteca_ag.copia (
    codice bigint NOT NULL,
    libro_isbn numeric(13,0) NOT NULL,
    stato character varying(16) DEFAULT 'disponibile'::character varying,
    sede_cod bigint,
    CONSTRAINT stato_check CHECK (((stato)::text = ANY ((ARRAY['disponibile'::character varying, 'non disponibile'::character varying])::text[])))
);


ALTER TABLE biblioteca_ag.copia OWNER TO andrea;

--
-- Name: copia_codice_seq; Type: SEQUENCE; Schema: biblioteca_ag; Owner: andrea
--

ALTER TABLE biblioteca_ag.copia ALTER COLUMN codice ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME biblioteca_ag.copia_codice_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: lettore; Type: TABLE; Schema: biblioteca_ag; Owner: andrea
--

CREATE TABLE biblioteca_ag.lettore (
    cf character(16) NOT NULL,
    nome character varying(255) NOT NULL,
    cognome character varying(255) NOT NULL,
    volumi_in_ritardo smallint DEFAULT 0 NOT NULL,
    categoria character varying(15) DEFAULT 'base'::character varying NOT NULL,
    CONSTRAINT lettore_categoria_check CHECK (((categoria)::text = ANY ((ARRAY['base'::character varying, 'premium'::character varying])::text[]))),
    CONSTRAINT lettore_volumi_in_ritardo_check CHECK (((volumi_in_ritardo < 6) AND (volumi_in_ritardo >= 0)))
);


ALTER TABLE biblioteca_ag.lettore OWNER TO andrea;

--
-- Name: libro; Type: TABLE; Schema: biblioteca_ag; Owner: andrea
--

CREATE TABLE biblioteca_ag.libro (
    isbn numeric(13,0) NOT NULL,
    titolo character varying(255) NOT NULL,
    trama text,
    casa_ed character varying(255)
);


ALTER TABLE biblioteca_ag.libro OWNER TO andrea;

--
-- Name: prestito; Type: TABLE; Schema: biblioteca_ag; Owner: andrea
--

CREATE TABLE biblioteca_ag.prestito (
    id bigint NOT NULL,
    data_inizio date DEFAULT CURRENT_DATE NOT NULL,
    copia_codice bigint,
    libro_isbn numeric(13,0) NOT NULL,
    lettore_cf character(16) NOT NULL,
    fine_concessione date DEFAULT (CURRENT_DATE + 30),
    restituzione date
);


ALTER TABLE biblioteca_ag.prestito OWNER TO andrea;

--
-- Name: prestito_id_seq; Type: SEQUENCE; Schema: biblioteca_ag; Owner: andrea
--

ALTER TABLE biblioteca_ag.prestito ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME biblioteca_ag.prestito_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: report_ritardi; Type: VIEW; Schema: biblioteca_ag; Owner: andrea
--

CREATE VIEW biblioteca_ag.report_ritardi AS
 SELECT copia.sede_cod,
    prestito.copia_codice,
    prestito.lettore_cf
   FROM (biblioteca_ag.copia
     JOIN biblioteca_ag.prestito ON ((copia.codice = prestito.copia_codice)))
  WHERE ((CURRENT_DATE > prestito.fine_concessione) AND ((copia.stato)::text = 'non disponibile'::text))
  ORDER BY copia.sede_cod;


ALTER VIEW biblioteca_ag.report_ritardi OWNER TO andrea;

--
-- Name: scrive; Type: TABLE; Schema: biblioteca_ag; Owner: andrea
--

CREATE TABLE biblioteca_ag.scrive (
    autore_id bigint NOT NULL,
    libro_isbn numeric(13,0) NOT NULL
);


ALTER TABLE biblioteca_ag.scrive OWNER TO andrea;

--
-- Name: sede; Type: TABLE; Schema: biblioteca_ag; Owner: andrea
--

CREATE TABLE biblioteca_ag.sede (
    cod bigint NOT NULL,
    "città" character varying(255) NOT NULL,
    indirizzo character varying(255) NOT NULL
);


ALTER TABLE biblioteca_ag.sede OWNER TO andrea;

--
-- Name: sede_cod_seq; Type: SEQUENCE; Schema: biblioteca_ag; Owner: andrea
--

ALTER TABLE biblioteca_ag.sede ALTER COLUMN cod ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME biblioteca_ag.sede_cod_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: statistiche_sedi; Type: VIEW; Schema: biblioteca_ag; Owner: andrea
--

CREATE VIEW biblioteca_ag.statistiche_sedi AS
 SELECT sede_cod AS sede,
    count(*) AS copie,
    count(DISTINCT libro_isbn) AS libri,
    count(
        CASE
            WHEN ((stato)::text = 'non disponibile'::text) THEN 1
            ELSE NULL::integer
        END) AS prestiti_attivi
   FROM biblioteca_ag.copia
  GROUP BY sede_cod;


ALTER VIEW biblioteca_ag.statistiche_sedi OWNER TO andrea;

--
-- Name: utente_bibliotecario; Type: TABLE; Schema: biblioteca_ag; Owner: andrea
--

CREATE TABLE biblioteca_ag.utente_bibliotecario (
    email text NOT NULL,
    password text NOT NULL,
    CONSTRAINT utente_bibliotecario_email_check CHECK ((email ~ '^[A-Za-z0-9._%-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$'::text))
);


ALTER TABLE biblioteca_ag.utente_bibliotecario OWNER TO andrea;

--
-- Name: utente_lettore; Type: TABLE; Schema: biblioteca_ag; Owner: andrea
--

CREATE TABLE biblioteca_ag.utente_lettore (
    email text NOT NULL,
    password text NOT NULL,
    cf_lettore character(16) NOT NULL,
    CONSTRAINT utente_lettore_email_check CHECK ((email ~ '^[A-Za-z0-9._%-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$'::text))
);


ALTER TABLE biblioteca_ag.utente_lettore OWNER TO andrea;

--
-- Data for Name: autore; Type: TABLE DATA; Schema: biblioteca_ag; Owner: andrea
--

COPY biblioteca_ag.autore (id, nome, cognome, data_nascita, data_morte, biografia) FROM stdin;
4	Harper	Lee	1926-04-28	2016-02-19	Harper Lee was an American novelist widely known for To Kill a Mockingbird, published in 1960.
5	F. Scott	Fitzgerald	1896-09-24	1940-12-21	F. Scott Fitzgerald was an American novelist famous for his work The Great Gatsby.
6	J.D.	Salinger	1919-01-01	2010-01-27	J.D. Salinger was an American writer known for his widely read novel, The Catcher in the Rye.
7	George	Orwell	1903-06-25	1950-01-21	George Orwell was an English novelist, essayist, journalist, and critic best known for his dystopian works.
8	J.K.	Rowling	1965-07-31	\N	J.K. Rowling is a British author, best known for writing the Harry Potter fantasy series.
9	Paulo	Coelho	1947-08-24	\N	Paulo Coelho is a Brazilian lyricist and novelist best known for his novel The Alchemist.
10	Cormac	McCarthy	1933-07-20	2023-06-13	Cormac McCarthy was an American novelist and playwright known for his works in Southern Gothic, western, and post-apocalyptic genres.
11	Antoine	de Saint-Exupéry	1900-06-29	1944-07-31	Antoine de Saint-Exupéry was a French writer and aviator, best remembered for his novella The Little Prince.
12	J.R.R.	Tolkien	1892-01-03	1973-09-02	J.R.R. Tolkien was an English writer, poet, philologist, and academic, best known for The Hobbit and The Lord of the Rings.
13	Khaled	Hosseini	1965-03-04	\N	Khaled Hosseini is an Afghan-American novelist and physician, best known for his novel The Kite Runner.
16	Cesare	Pavese	1908-09-09	1950-08-27	Grande scrittore antifascista.
\.


--
-- Data for Name: copia; Type: TABLE DATA; Schema: biblioteca_ag; Owner: andrea
--

COPY biblioteca_ag.copia (codice, libro_isbn, stato, sede_cod) FROM stdin;
14	9780544003415	disponibile	11
10	9780062316097	disponibile	4
12	9780307476708	disponibile	8
13	9780307476708	disponibile	9
4	9780062316097	disponibile	3
16	9780307476708	disponibile	11
18	9788806221300	disponibile	7
\.


--
-- Data for Name: lettore; Type: TABLE DATA; Schema: biblioteca_ag; Owner: andrea
--

COPY biblioteca_ag.lettore (cf, nome, cognome, volumi_in_ritardo, categoria) FROM stdin;
RSSMRA85M01H501Z	Marco	Rossi	0	base
FBRGFR83P12C354D	Giorgio	Fabbri	0	premium
SLVRGL85H05H501L	Giulia	Salvadori	0	premium
CNTFNC95A01E512K	Francesco	Conti	0	base
LBRMRZ60S10C351N	Marzia	Liberati	0	premium
NCCFNC70M01H501F	Nicola	Cenci	0	base
RCCLST92C15F205C	Alessandra	Ricci	0	premium
GRRNDR03C08F205Y	Andrea	Gerardi	0	base
VRDLGI75C05H501W	Luigi	Verdi	0	base
PRSMNL70D10G273J	Manuela	Piersanti	0	base
NNNGNN54H54I726T	Gianna	Nannini	0	base
BNCLRA90E15F205Y	Laura	Bianchi	0	base
\.


--
-- Data for Name: libro; Type: TABLE DATA; Schema: biblioteca_ag; Owner: andrea
--

COPY biblioteca_ag.libro (isbn, titolo, trama, casa_ed) FROM stdin;
9780062316097	To Kill a Mockingbird	A novel set in the American South during the 1930s, addressing serious issues like racial inequality.	Harper Perennial Modern Classics
9780743273565	The Great Gatsby	A critique of the American Dream, narrated by Nick Carraway about the mysterious Jay Gatsby.	Scribner
9780316769488	The Catcher in the Rye	A story about teenage alienation and loss of innocence, narrated by the disillusioned Holden Caulfield.	Little, Brown and Company
9780140283334	1984	A dystopian novel depicting a totalitarian regime that uses surveillance, censorship, and control to manipulate society.	Penguin Books
9780061120084	The Alchemist	A philosophical story about a shepherd named Santiago who dreams of finding treasure in the pyramids of Egypt.	HarperOne
9780307476708	The Road	A post-apocalyptic tale of a father and son journeying through a devastated landscape.	Vintage International
9780385490818	The Little Prince	A philosophical novella exploring themes of love, friendship, and the meaning of life through the eyes of a child.	Harcourt, Brace & World
9780544003415	The Hobbit	The prelude to The Lord of the Rings trilogy, following Bilbo Baggins’ adventure to reclaim a lost Dwarf Kingdom.	Houghton Mifflin Harcourt
9780307277671	The Kite Runner	A story of friendship and redemption set against the backdrop of a changing Afghanistan.	Riverhead Books
9788806221300	La casa in collina	La storia di una solitudine individuale di fronte all'impegno civile e storico; la contraddizione da risolvere tra vita in campagna e vita in città, nel caos della guerra; il superamento dell'egoismo attraverso la scoperta che ogni caduto somiglia a chi resta e gliene chiede ragione. "Ora che ho visto cos'è la guerra civile, so che tutti, se un giorno finisse, dovrebbero chiedersi: "E dei caduti che facciamo? Perché sono morti?" Io non saprei cosa rispondere. Non adesso almeno. Né mi pare che gli altri lo sappiano. Forse lo sanno unicamente i morti, e soltanto per loro la guerra è finita davvero". La grande intuizione delle ultime pagine de "La casa in collina" sarà ripresa e portata alle estreme conseguenze artistiche e morali nell'altro grande libro di Cesare Pavese, "La luna e i falò".	Einaudi
\.


--
-- Data for Name: prestito; Type: TABLE DATA; Schema: biblioteca_ag; Owner: andrea
--

COPY biblioteca_ag.prestito (id, data_inizio, copia_codice, libro_isbn, lettore_cf, fine_concessione, restituzione) FROM stdin;
10	2024-09-11	10	9780062316097	BNCLRA90E15F205Y	2024-10-11	2024-09-11
11	2024-09-11	4	9780062316097	BNCLRA90E15F205Y	2024-10-11	2024-09-11
27	2024-09-11	10	9780062316097	BNCLRA90E15F205Y	2024-10-11	2024-09-11
28	2024-09-11	4	9780062316097	BNCLRA90E15F205Y	2024-10-11	2024-09-11
33	2024-09-11	10	9780062316097	BNCLRA90E15F205Y	2024-11-10	2024-09-13
34	2024-09-15	12	9780307476708	BNCLRA90E15F205Y	2024-10-15	2024-09-15
35	2024-09-15	13	9780307476708	BNCLRA90E15F205Y	2024-10-15	2024-09-15
37	2024-09-18	4	9780062316097	BNCLRA90E15F205Y	2024-10-18	2024-11-11
38	2024-09-16	4	9780062316097	BNCLRA90E15F205Y	2024-09-17	2024-09-18
39	2024-09-18	16	9780307476708	BNCLRA90E15F205Y	2024-10-18	2024-09-18
\.


--
-- Data for Name: scrive; Type: TABLE DATA; Schema: biblioteca_ag; Owner: andrea
--

COPY biblioteca_ag.scrive (autore_id, libro_isbn) FROM stdin;
4	9780062316097
5	9780743273565
6	9780316769488
7	9780140283334
9	9780061120084
10	9780307476708
11	9780385490818
12	9780544003415
13	9780307277671
16	9788806221300
\.


--
-- Data for Name: sede; Type: TABLE DATA; Schema: biblioteca_ag; Owner: andrea
--

COPY biblioteca_ag.sede (cod, "città", indirizzo) FROM stdin;
2	Roma	Via del Corso, 123
3	Milano	Corso Buenos Aires, 45
4	Torino	Via Roma, 10
6	Firenze	Piazza della Repubblica, 1
7	Bologna	Via Indipendenza, 32
8	Venezia	Calle Larga, 78
9	Palermo	Via Maqueda, 56
10	Genova	Via XX Settembre, 15
11	Verona	Via Mazzini, 23
\.


--
-- Data for Name: utente_bibliotecario; Type: TABLE DATA; Schema: biblioteca_ag; Owner: andrea
--

COPY biblioteca_ag.utente_bibliotecario (email, password) FROM stdin;
biblio@tecario.it	$2y$10$qDJSQzR7A5MJM5Jfvt326eZg0yz2qx1lv/TXnxpqDXm2Fubh8BUYW
\.


--
-- Data for Name: utente_lettore; Type: TABLE DATA; Schema: biblioteca_ag; Owner: andrea
--

COPY biblioteca_ag.utente_lettore (email, password, cf_lettore) FROM stdin;
laura@bianchi.it	$2y$10$Wkf7Q3JjCOx.9W8.FGUSOeLPv59He50MvuMeLDiZkYdWHgkxC1eki	BNCLRA90E15F205Y
giannanannini@gmail.com	$2y$10$KcNZ59vpVf6XJFUj..AoF.zUPRfOeKvMMs9JwZglGM7Q5.2/.IwSi	NNNGNN54H54I726T
\.


--
-- Name: autore_id_seq; Type: SEQUENCE SET; Schema: biblioteca_ag; Owner: andrea
--

SELECT pg_catalog.setval('biblioteca_ag.autore_id_seq', 17, true);


--
-- Name: copia_codice_seq; Type: SEQUENCE SET; Schema: biblioteca_ag; Owner: andrea
--

SELECT pg_catalog.setval('biblioteca_ag.copia_codice_seq', 18, true);


--
-- Name: prestito_id_seq; Type: SEQUENCE SET; Schema: biblioteca_ag; Owner: andrea
--

SELECT pg_catalog.setval('biblioteca_ag.prestito_id_seq', 39, true);


--
-- Name: sede_cod_seq; Type: SEQUENCE SET; Schema: biblioteca_ag; Owner: andrea
--

SELECT pg_catalog.setval('biblioteca_ag.sede_cod_seq', 14, true);


--
-- Name: autore autore_pkey; Type: CONSTRAINT; Schema: biblioteca_ag; Owner: andrea
--

ALTER TABLE ONLY biblioteca_ag.autore
    ADD CONSTRAINT autore_pkey PRIMARY KEY (id);


--
-- Name: copia copia_pkey; Type: CONSTRAINT; Schema: biblioteca_ag; Owner: andrea
--

ALTER TABLE ONLY biblioteca_ag.copia
    ADD CONSTRAINT copia_pkey PRIMARY KEY (codice);


--
-- Name: lettore lettore_pkey; Type: CONSTRAINT; Schema: biblioteca_ag; Owner: andrea
--

ALTER TABLE ONLY biblioteca_ag.lettore
    ADD CONSTRAINT lettore_pkey PRIMARY KEY (cf);


--
-- Name: libro libro_pkey; Type: CONSTRAINT; Schema: biblioteca_ag; Owner: andrea
--

ALTER TABLE ONLY biblioteca_ag.libro
    ADD CONSTRAINT libro_pkey PRIMARY KEY (isbn);


--
-- Name: prestito prestito_pkey; Type: CONSTRAINT; Schema: biblioteca_ag; Owner: andrea
--

ALTER TABLE ONLY biblioteca_ag.prestito
    ADD CONSTRAINT prestito_pkey PRIMARY KEY (id);


--
-- Name: scrive scrive_pkey; Type: CONSTRAINT; Schema: biblioteca_ag; Owner: andrea
--

ALTER TABLE ONLY biblioteca_ag.scrive
    ADD CONSTRAINT scrive_pkey PRIMARY KEY (autore_id, libro_isbn);


--
-- Name: sede sede_pkey; Type: CONSTRAINT; Schema: biblioteca_ag; Owner: andrea
--

ALTER TABLE ONLY biblioteca_ag.sede
    ADD CONSTRAINT sede_pkey PRIMARY KEY (cod);


--
-- Name: utente_bibliotecario utente_bibliotecario_pkey; Type: CONSTRAINT; Schema: biblioteca_ag; Owner: andrea
--

ALTER TABLE ONLY biblioteca_ag.utente_bibliotecario
    ADD CONSTRAINT utente_bibliotecario_pkey PRIMARY KEY (email);


--
-- Name: utente_lettore utente_lettore_pkey; Type: CONSTRAINT; Schema: biblioteca_ag; Owner: andrea
--

ALTER TABLE ONLY biblioteca_ag.utente_lettore
    ADD CONSTRAINT utente_lettore_pkey PRIMARY KEY (email);


--
-- Name: prestito aggiorna_disponibilita; Type: TRIGGER; Schema: biblioteca_ag; Owner: andrea
--

CREATE TRIGGER aggiorna_disponibilita AFTER INSERT ON biblioteca_ag.prestito FOR EACH ROW EXECUTE FUNCTION biblioteca_ag.aggiorna_disponibilita_funzione();


--
-- Name: prestito aggiorna_ritardi; Type: TRIGGER; Schema: biblioteca_ag; Owner: andrea
--

CREATE TRIGGER aggiorna_ritardi AFTER UPDATE OF restituzione ON biblioteca_ag.prestito FOR EACH ROW EXECUTE FUNCTION biblioteca_ag.aggiorna_ritardi_funzione();


--
-- Name: prestito blocco_prestiti; Type: TRIGGER; Schema: biblioteca_ag; Owner: andrea
--

CREATE TRIGGER blocco_prestiti BEFORE INSERT ON biblioteca_ag.prestito FOR EACH ROW EXECUTE FUNCTION biblioteca_ag.blocco_presititi_funzione();


--
-- Name: prestito check_proroga; Type: TRIGGER; Schema: biblioteca_ag; Owner: andrea
--

CREATE TRIGGER check_proroga BEFORE UPDATE OF fine_concessione ON biblioteca_ag.prestito FOR EACH ROW EXECUTE FUNCTION biblioteca_ag.check_proroga_funzione();


--
-- Name: prestito controlla_disponibilita; Type: TRIGGER; Schema: biblioteca_ag; Owner: andrea
--

CREATE TRIGGER controlla_disponibilita BEFORE INSERT ON biblioteca_ag.prestito FOR EACH ROW EXECUTE FUNCTION biblioteca_ag.controlla_disponibilita_funzione();


--
-- Name: prestito rendi_disponibile; Type: TRIGGER; Schema: biblioteca_ag; Owner: andrea
--

CREATE TRIGGER rendi_disponibile AFTER UPDATE OF restituzione ON biblioteca_ag.prestito FOR EACH ROW EXECUTE FUNCTION biblioteca_ag.rendi_disponibile_function();


--
-- Name: prestito tetto_prestiti; Type: TRIGGER; Schema: biblioteca_ag; Owner: andrea
--

CREATE TRIGGER tetto_prestiti BEFORE INSERT ON biblioteca_ag.prestito FOR EACH ROW EXECUTE FUNCTION biblioteca_ag.tetto_prestiti_funzione();


--
-- Name: copia copia_libro_isbn_fkey; Type: FK CONSTRAINT; Schema: biblioteca_ag; Owner: andrea
--

ALTER TABLE ONLY biblioteca_ag.copia
    ADD CONSTRAINT copia_libro_isbn_fkey FOREIGN KEY (libro_isbn) REFERENCES biblioteca_ag.libro(isbn);


--
-- Name: copia copia_sede_cod_fkey; Type: FK CONSTRAINT; Schema: biblioteca_ag; Owner: andrea
--

ALTER TABLE ONLY biblioteca_ag.copia
    ADD CONSTRAINT copia_sede_cod_fkey FOREIGN KEY (sede_cod) REFERENCES biblioteca_ag.sede(cod);


--
-- Name: prestito prestito_copia_codice_fkey; Type: FK CONSTRAINT; Schema: biblioteca_ag; Owner: andrea
--

ALTER TABLE ONLY biblioteca_ag.prestito
    ADD CONSTRAINT prestito_copia_codice_fkey FOREIGN KEY (copia_codice) REFERENCES biblioteca_ag.copia(codice);


--
-- Name: prestito prestito_lettore_cf_fkey; Type: FK CONSTRAINT; Schema: biblioteca_ag; Owner: andrea
--

ALTER TABLE ONLY biblioteca_ag.prestito
    ADD CONSTRAINT prestito_lettore_cf_fkey FOREIGN KEY (lettore_cf) REFERENCES biblioteca_ag.lettore(cf);


--
-- Name: prestito prestito_libro_isbn_fkey; Type: FK CONSTRAINT; Schema: biblioteca_ag; Owner: andrea
--

ALTER TABLE ONLY biblioteca_ag.prestito
    ADD CONSTRAINT prestito_libro_isbn_fkey FOREIGN KEY (libro_isbn) REFERENCES biblioteca_ag.libro(isbn);


--
-- Name: scrive scrive_autore_id_fkey; Type: FK CONSTRAINT; Schema: biblioteca_ag; Owner: andrea
--

ALTER TABLE ONLY biblioteca_ag.scrive
    ADD CONSTRAINT scrive_autore_id_fkey FOREIGN KEY (autore_id) REFERENCES biblioteca_ag.autore(id);


--
-- Name: scrive scrive_libro_isbn_fkey; Type: FK CONSTRAINT; Schema: biblioteca_ag; Owner: andrea
--

ALTER TABLE ONLY biblioteca_ag.scrive
    ADD CONSTRAINT scrive_libro_isbn_fkey FOREIGN KEY (libro_isbn) REFERENCES biblioteca_ag.libro(isbn);


--
-- Name: utente_lettore utente_lettore_cf_lettore_fkey; Type: FK CONSTRAINT; Schema: biblioteca_ag; Owner: andrea
--

ALTER TABLE ONLY biblioteca_ag.utente_lettore
    ADD CONSTRAINT utente_lettore_cf_lettore_fkey FOREIGN KEY (cf_lettore) REFERENCES biblioteca_ag.lettore(cf);


--
-- Name: SCHEMA biblioteca_ag; Type: ACL; Schema: -; Owner: pg_database_owner
--

GRANT ALL ON SCHEMA biblioteca_ag TO postgres WITH GRANT OPTION;


--
-- PostgreSQL database dump complete
--

