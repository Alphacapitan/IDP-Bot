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

        // caso -> fallback
        $botman->fallback(function(Botman $bot) {
            $bot->reply("Puoi chiedermi l'orario di: \n👉 Oggi \n👉 Domani \n👉 Questa settimana \n👉 Settimana prossima");
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
     * Converte gli eventi in messaggio
     * @return string
     */
    public static function formatEvent($bot, $collection) 
    {
        // controlla se esistono eventi nella collection
        if ($collection->isNotEmpty()) {
            
            // converte la collection in json
            $collection->toJSON();
            // crea la collection di dati base --> verranno poi aggiunti i dati degli eventi
            $baseCollection = collect();

            foreach ($collection as $key => $item) {
                // data di inizio
                $dt_Init = Carbon::parse($item->googleEvent->start->dateTime);
                // data di fine
                $dt_End = Carbon::parse($item->googleEvent->end->dateTime);
                
                // crea l'array con i dati per i singoli eventi
                $baseCollection->push(
                    array(
                        "date" => Carbon::createFromDate($dt_Init->year, $dt_Init->month, $dt_Init->day)->format('d / m'),
                        "day" => Carbon::createFromDate($dt_Init->year, $dt_Init->month, $dt_Init->day)->format('l'),
                        "title" => $item->googleEvent->summary,
                        "desc" => $item->googleEvent->description,
                        "inizio" => Carbon::createFromTime($dt_Init->hour, $dt_Init->minute)->format('H:i'),
                        "fine" => Carbon::createFromTime($dt_End->hour, $dt_End->minute)->format('H:i'),
                    )
                );
            }
            // crea il messaggio
            $message = "";

            // inizio scrittura messaggio
            foreach ($baseCollection as $key => $event) {
                
                // controlla se il giorno del evento attuale nel ciclo è uguale o diverso dall'evento prima
                if ($key == 0 || $event["day"] != $baseCollection[$key - 1]["day"]) {
                    // in sia il primo evento o diverso aggiunge il giorno dell'evento
                    $message = $message . "🗓" . __("day." . $event["day"]) . " - " . $event["date"] . "\n\n";
                }

                // compila il messaggio
                $message = $message . "📒 $event[title] - $event[desc]" . "\n" . "⏱ Dalle $event[inizio] Alle $event[fine]" . "\n\n";
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

