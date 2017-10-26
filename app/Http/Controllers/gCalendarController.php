<?php 
namespace App\Http\Controllers;

use Illuminate\Http\Request;

use \Spatie\GoogleCalendar\Event;

use Carbon\Carbon;

class gCalendarController extends Controller 
{
    public function getTodayEvents() {
        // ricava gli eventi a partire da data di inizio e di fine
        $events = Event::get(Carbon::today(), Carbon::today()->addHour(23));
        
        // Settimana --> todo
        // echo Carbon::today()->startOfWeek();

        $results = $this->formatEvent($events);
        echo $results;
    }

    public function getTomorrowEvents() {
        // ricava gli eventi a partire da data di inizio e di fine
        $events = Event::get(Carbon::tomorrow(), Carbon::tomorrow()->addHour(23));

        $results = $this->formatEvent($events);
        echo $results;
    }

    public function formatEvent($collection) {

        //debug
        // $collection->dd();

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
            $message = $message . "
            $value[title] - $value[desc]
            Dalle $value[inizio] Alle $value[fine]
            ";
        }
        // ritorno il messaggio
        return $message;
    }
}