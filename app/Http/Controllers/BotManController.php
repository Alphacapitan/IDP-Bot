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

        $botman->hears('^.*(\Bene).*$', function ($bot) {
            $bot->types();
            $bot->reply('Ma non benissimo 😎!');
        });

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
            $bot->reply("Grandissima la ragazza che la ride... YAAA 😘");
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

    /**
     * Loaded through routes/botman.php
     * @param  BotMan $bot
     */
    public function startConversation(BotMan $bot)
    {
        $bot->startConversation(new ExampleConversation());
    }

    public function getTodayEvents() {
        // ricava gli eventi a partire da data di inizio e di fine
        $events = Event::get(Carbon::today(), Carbon::today()->addHour(23));
        return $events;
    }

    public function getTomorrowEvents() {
        // ricava gli eventi a partire da data di inizio e di fine
        $events = Event::get(Carbon::tomorrow(), Carbon::tomorrow()->addHour(23));
        return $events;
    }

    public function formatEvent($collection) {
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
                    "inizio" => Carbon::createFromFormat('H-m', $dt_Init->hour . "-" . $dt_Init->minute)->format('H:i'),
                    "fine" => Carbon::createFromFormat('H-m', $dt_End->hour . "-" . $dt_End->minute)->format('H:i'),
                )
            );

        }
        // crea il messaggio
        $message = "";
        foreach ($baseCollection as $key => $value) {
$message = $message . "
$value[title] - $value[desc]
Dalle $value[inizio] Alle $value[fine]
";
        }
        // ritorno il messaggio
        return $message;
    }

}

