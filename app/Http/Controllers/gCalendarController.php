<?php 
namespace App\Http\Controllers;

use Illuminate\Http\Request;

use \Spatie\GoogleCalendar\Event;

use Carbon\Carbon;

class gCalendarController extends Controller 
{
    public function getAllEvents() {
        $events = Event::get(Carbon::tomorrow(), Carbon::tomorrow()->addHours(23));
        // $events->dd();
        $events->each(function ($item, $key){
            echo $item->googleEvent->summary;
            echo "<br/>";
            $dt = Carbon::parse($item->googleEvent->start->dateTime);
            echo Carbon::createFromFormat('H-m', $dt->hour . "-" . $dt->minute)->toTimeString();
            echo "<br/>";
            $dt = Carbon::parse($item->googleEvent->end->dateTime);
            echo Carbon::createFromFormat('H-m', $dt->hour . "-" . $dt->minute)->toTimeString();
            echo "<br/>";
        });
    }
}