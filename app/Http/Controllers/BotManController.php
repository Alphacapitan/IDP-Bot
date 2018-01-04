<?php

namespace App\Http\Controllers;

use BotMan\BotMan\BotMan;
use Illuminate\Http\Request;
use Spatie\GoogleCalendar\Event;
use Carbon\Carbon;

use App\Conversations\calendarConversation;

class BotManController extends Controller
{
    //
    // Bot logic
    //

    public function handle()
    {
        $botman = app('botman');

        $botman->hears('Orario', function($bot) {
            $bot->startConversation(new calendarConversation);
        });

        // caso -> oggi
        $botman->hears('oggi', function ($bot) {
            $events = $this->getTodayEvents();
            $bot->typesAndWaits(1);
            $results = $this->formatEvent($bot, $events);
            $bot->reply($results);
        });

        // caso -> domani
        $botman->hears('domani', function ($bot) {
            $events = $this->getTomorrowEvents();
            $bot->typesAndWaits(1);
            $results = $this->formatEvent($bot, $events);
            $bot->reply($results);
        });

        // caso -> questa settimana
        $botman->hears('questa settimana', function ($bot) {
            $events = $this->getCurrentWeekEvents();
            $bot->typesAndWaits(1);
            $results = $this->formatEvent($bot, $events);
            $bot->reply($results);
        });

        // caso -> settimana prossima
        $botman->hears('settimana prossima', function ($bot) {
            $events = $this->getNextWeekEvents();
            $bot->typesAndWaits(1);
            $results = $this->formatEvent($bot, $events);
            $bot->reply($results);
        });

        // caso -> prossimo esame
        $botman->hears('esami', function ($bot) {
            $events = $this->listNextExams();
            $bot->typesAndWaits(1);
            $results = $this->formatEvent($bot, $events, true);
            $bot->reply($results);
        });

        // caso -> esami
        $botman->hears('prossimo esame', function ($bot) {
            $events = $this->getNextExam();
            $bot->typesAndWaits(1);
            $results = $this->formatEvent($bot, $events, true);
            // array di stickers
            $sticker = array(
                'CAADAQADpDUAAtpxZgfg60uzvw8DjAI',
                'CAADAwAD2QMAAqbJWAABzbeLe8I95rMC',
                'CAADAgAD0AADMictBz4756bDDdlwAg'
            );
            // invia lo stickers del prossimo esame
            $bot->sendRequest('sendSticker', [
                // invia uno sticker a random
                'sticker' => $sticker[rand( 0 , count($sticker) )]
            ]);
            // risposta
            $bot->reply($results);
        });

        // caso -> fallback
        $botman->fallback(function(Botman $bot) {
            $bot->reply("Puoi chiedermi l'orario di: \n👉 Oggi \n👉 Domani \n👉 Questa settimana \n👉 Settimana prossima \n👉 Esami \n👉 Prossimo esame");
        });

        // ascolto
        $botman->listen();
    }
    
    /**
     * Carica gli eventi di oggi
     * @return collection
     */
    public function getTodayEvents() 
    {
        // ricava gli eventi a partire da data di inizio e di fine
        $events = Event::get(Carbon::today(), Carbon::today()->endOfDay());
        return $events;
    }

    /**
     * Carica gli eventi di domani
     * @return collection
     */
    public function getTomorrowEvents() 
    {
        // ricava gli eventi a partire da data di inizio e di fine
        $events = Event::get(Carbon::tomorrow(), Carbon::tomorrow()->endOfDay());
        return $events;
    }

    /**
     * Carica gli eventi di domani
     * @return collection
     */
    public function getCurrentWeekEvents() 
    {
        // ricava gli eventi a partire da data di inizio e di fine
        $events = Event::get(Carbon::today()->startOfWeek(), Carbon::today()->startOfWeek()->addDay(5));
        return $events;
    }

    /**
     * Carica gli eventi di domani
     * @return collection
     */
    public function getNextWeekEvents() 
    {
        // ricava gli eventi a partire da data di inizio e di fine
        $events = Event::get(Carbon::today()->startOfWeek()->next(Carbon::MONDAY), Carbon::today()->startOfWeek()->next(Carbon::MONDAY)->addDay(5));
        return $events;
    }

    /**
     * Carica la lista degli esami
     * @return collection
     */
    public function listNextExams() 
    {
        // ricava gli eventi a partire da data di inizio e di fine
        $events = Event::get(Carbon::now(), Carbon::now()->endOfYear(), [
            'q' => 'Esame'
        ]);
        return $events;
    }

    /**
     * Carica il prossimo esame
     * @return collection
     */
    public function getNextExam() 
    {
        // ricava gli eventi a partire da data di inizio e di fine
        $events = Event::get(Carbon::now(), Carbon::now()->endOfYear(), [
            'orderBy' => 'startTime',
            'q' => 'Esame',
            'maxResults' => 1
        ]);
        return $events;
    }

    /**
     * Converte gli eventi in messaggio
     * @return string
     */
    public static function formatEvent($bot, $collection, $exam = false) 
    {
        // controlla se esistono eventi nella collection
        if ($collection->isNotEmpty()) {
            
            // converte la collection in json
            $collection->toJSON();
            // crea la collection di dati base --> verranno poi aggiunti i dati degli eventi
            $baseCollection = collect();

            foreach ($collection as $key => $item) {
                // data di inizio
                if (!$exam) {
                    $da_Start = Carbon::parse($item->googleEvent->start->dateTime);
                } else {
                    $da_Start = Carbon::parse($item->googleEvent->start->date);
                }
                // data di fine
                $dt_End = Carbon::parse($item->googleEvent->end->dateTime);
                
                // crea l'array con i dati per i singoli eventi
                $baseCollection->push(
                    array(
                        "date" => Carbon::createFromDate($da_Start->year, $da_Start->month, $da_Start->day)->format('d / m'),
                        "day" => Carbon::createFromDate($da_Start->year, $da_Start->month, $da_Start->day)->format('l'),
                        "title" => $item->googleEvent->summary,
                        "desc" => trim($item->googleEvent->description, "\n\t"),
                        "inizio" => Carbon::createFromTime($da_Start->hour, $da_Start->minute)->format('H:i'),
                        "fine" => Carbon::createFromTime($dt_End->hour, $dt_End->minute)->format('H:i'),
                    )
                );
            }

            // crea il messaggio
            $message = "";

            // inizio scrittura messaggio
            foreach ($baseCollection as $key => $event) {
                
                // controlla se il giorno del evento attuale nel ciclo è uguale o diverso dall'evento prima
                if ($key == 0 || $event["date"] != $baseCollection[$key - 1]["date"]) {
                    // se primo evento o diverso aggiunge il giorno dell'evento
                    $message .= "🗓\t" . __("day." . $event["day"]) . " - " . $event["date"] . "\n\n";
                }

                // compila il messaggio
                // titolo dell'evento
                $message .= "• $event[title]";
                // descrizione -> se esiste dell'evento
                $message .= $event['desc'] ? " - $event[desc]" : "" . "\n";
                // se non è una lista esami
                if (!$exam) {
                    // date dell'evento
                    $message .= "• Dalle $event[inizio] Alle $event[fine]";
                }
                $message .= "\n\n";
            }

            // ritorno il messaggio
            return $message;

        } else {
            $sticker = array(
                'CAADAwADvAMAAqbJWAABK3w6QpBbOb4C',
                'CAADAQADpzUAAtpxZgcgK5pzKYYkGQI',
                'CAADBAADSQEAAjewwAAB0dGGAAFbBkUXAg',
                'CAADAgADHAADyIsGAAFzjQavel2uswI'
            );
            // caso senza eventi
            // invia lo stickers delle mancate lezioni
            $bot->sendRequest('sendSticker', [
                // invia uno sticker a random
                'sticker' => $sticker[rand( 0 , count($sticker) )]
            ]);
            // ritorno il messaggio che non ci sono lezioni
            return "Non ci sono lezioni ✋";
        }
        
    }
}

