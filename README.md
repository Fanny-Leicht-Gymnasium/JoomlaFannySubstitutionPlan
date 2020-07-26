# Fanny Substitution Plan
1. Setup:
    - Installation des Plugins über den Plugin Manager von Joomla
    - Konfiguration anpassen (Globale Konfiguration -> Fanny Substitution Plan)
    - Wichtig hierbei:
        - Bei den Dateipfaden die vollständigen Pfade inklusive Dateinamen aber OHNE Endung angeben. (die pdf und txt Dateien müssen gleich heißen und die Endung .pdf/.txt haben!)
        - Die richtigen Zugriffslevel einstellen, diese sorgen dafür, dass Lehrer automatisch zum Lehrer Vertretungsplan weitergeleitet werden und Schüler diesen nicht aufrufen können.

2. Benutzung:
    - Aufruf über: index.php/component/fannysubstitutionplan

    1. Als HTML Frontend:
        - GET Parameter:
            - mode:
                - '0' -> Schülervertretungsplan
                - '1' -> Lehrervertretungsplan
                - (wenn nicht angegeben automatisch Weiterleitung je nach Benutzerrechten)
            - day:
                - '0' -> Heute
                - '1' -> Morgen
                - (wenn nicht angegeben immer heute)
        - Beispiele:
            - http://www.fanny-leicht.de/j34/index.php/component/fannysubstitutionplan?day=0 (Tag 'heute' Modus automatisch)
            - http://www.fanny-leicht.de/j34/index.php/component/fannysubstitutionplan?day=1 (Tag 'morgen' Modus automatisch)

    2. Als PDF:
        - GET Parameter:
            - task:
                - 'downloadPdf' -> statt der HTML Tabelle wird die PDF-Datei heruntergeladen
        - Beispiel:
            - http://www.fanny-leicht.de/j34/index.php/component/fannysubstitutionplan?task=downloadPdf&day=0 (Tag 'heute' Modus automatisch)

    3. Als JSON API:
        - GET Parameter:
            - task:
                - 'api_login': 
                    -> Funktion um einen Anmeldetoken für die API zu bekommen (token = base64_encode( <userId>:<passwortHash> ))
                    -> benötigt GET Parameter 'username' und 'password'!
                - 'api_getData': 
                    -> Funktion um die Vertretungsplan-Daten als JSON abzurufen
                    -> benötigt GET Parameter 'token'!
            - username: Benutzername (wird benötigt für 'api_login')
            - password: Passwort (wird benötigt für 'api_login')
            - loginIsBase64:
                - 'true': Benutzername und Passwort werden nicht als Plaintext sondern als base64 String übergeben
                - 'false': Benutzername und Passwort werden als Plaintext übergeben
                - standard: 'false'
            - token: Anmeldetoken (erhältlich durch 'api_login', wird benötigt für 'api_getData')
        - Beispiel:
            - http://www.fanny-leicht.de/j34/index.php/component/fannysubstitutionplan?mode=1&task=api_login&username=<Benutzername>&password=<Passwort> (holt Anmeldetoken)
            - http://www.fanny-leicht.de/j34/index.php/component/fannysubstitutionplan?mode=1&task=api_getData&mode=0&day=0&token=<Token> (holt Vertretungsplan Daten für Schüler, heute)

# ACHTUNG:
- Die JSON API wird nur dann aufgerufen, wenn der Client nicht im Joomla-Frontend angemeldet ist, sonst erscheint die normale Tabelle!

