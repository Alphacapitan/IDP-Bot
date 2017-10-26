<?php

namespace App\Http\Controllers;

use BotMan\BotMan\BotMan;
use Illuminate\Http\Request;
use App\Conversations\ExampleConversation;

use \Spatie\GoogleCalendar\Event;
use Carbon\Carbon;


class BotManController extends Controller
{
    /**
     * Place your BotMan logic here.
     */
    public function handle()
    {
        $botman = app('botman');

        $botman->hears('oggi', function ($bot) {
            $events = $this->getTodayEvents();
            $results = $this->formatEvent($events);
            $bot->reply($results);
        });

        $botman->hears('domani', function ($bot) {
            $events = $this->getTomorrowEvents();
            $results = $this->formatEvent($events);
            $bot->reply($results);
        });

        $botman->fallback(function(Botman $bot) {
            $bot->reply("Puoi chiedermi l'orario di oggi e l'orario di domani");
        });

        $botman->listen();
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function tinker()
    {
        return view('tinker');
    }

    public function getTodayEvents() {
        /**
         * Carica gli eventi di oggi
         * @return collection
         */
        // ricava gli eventi a partire da data di inizio e di fine
        $events = Event::get(Carbon::today(), Carbon::today()->addHour(23));
        return $events;
    }

    public function getTomorrowEvents() {
        /**
         * Carica gli eventi di domani
         * @return collection
         */
        // ricava gli eventi a partire da data di inizio e di fine
        $events = Event::get(Carbon::tomorrow(), Carbon::tomorrow()->addHour(23));
        return $events;
    }

    public function formatEvent($collection) {
        /**
         * Converte gli eventi in messaggio
         * @return string
         */

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
                        "title" => $item->googleEvent->summary,
                        "desc" => $item->googleEvent->description,
                        "inizio" => Carbon::createFromTime($dt_Init->hour, $dt_Init->minute)->format('H:i'),
                        "fine" => Carbon::createFromTime($dt_End->hour, $dt_End->minute)->format('H:i'),
                    )
                );

            }
            // crea il messaggio
            $message = "";
            foreach ($baseCollection as $key => $value) {
                $message = $message . "📒 $value[title] - $value[desc]" . "\n" . "⏱ Dalle $value[inizio] Alle $value[fine]" . "\n\n";
            }
            // ritorna il messaggio
            return $message;
        } else {
            return "Non ci sono lezioni ✋";
        }
    }

}

