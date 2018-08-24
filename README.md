[![StyleCI](https://github.styleci.io/repos/145919264/shield?branch=master)](https://github.styleci.io/repos/145919264)

# IPS EHZ
Modul IPS EHZ(Elektronische Haushaltzähler)

Das Modul wertet die vom Zähler freigegebenen Daten aus.
Das Repository wird über GitHub bereitgestellt und über Module Control eingebunden.
Es ist einfach gehalten und benötigt nur die Eingabe der Daten je nach Auswahl der Schnittstelle.

Nutzbar ab IPS V4.1!

Bedienung:

Auswahl Client Socket oder SerialPort.
Nach Eingabe der Daten und speichern, legt das Modul die entsprechenden Variablen
welche durch den Zähler freigegeben sind automatisch an.
Diese sind ja nach EVU sehr unterschiedlich. Im Normalfall sollte ein PIN zum freischalten dem Zähler beiliegen oder beim zuständigen EVU nachfragen.
Statische Werte z.B Hersteller, ServerID usw. werden nicht angelegt. Wer diese benötigt kann dies selber über eigene Variablen erstellen.
Es werden keine Variablen geloggt, dieses ist auch selber einzustellen.
Der Zähler sendet in regelmäßigen Abständen Daten(1-10sek.).
Es steht kein Timer zur Verfügung da es relativ Echtzeitdaten sind.
Wer Bedenken wegen der Datenmenge hat, kann sich Hilfsvariablen anlegen und diese loggen.

Bei Problemen:

Im Modul auf Debug klicken. Dort kann man dann einen entsprechenden Auszug durch anklicken speichern und hier posten oder per PM senden.
Eventuelle Unstimmigkeiten bei der Darstellung der Werte(Kommaverschiebung usw.) bitte ebenfalls per Debug.

Nutzung:

Das Modul ist für die private Nutzung der Community gedacht!

Für alles andere, würde ich bitten anzufragen.
