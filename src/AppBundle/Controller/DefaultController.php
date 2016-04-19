<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

define('APPLICATION_NAME', 'Tientje <3');
define('CREDENTIALS_PATH', '~/.credentials/calendar-php-quickstart.json');
define('CLIENT_SECRET_PATH', __DIR__ . '/../../../client_secret.json');
// If modifying these scopes, delete your previously saved credentials
// at ~/.credentials/calendar-php-quickstart.json
define('SCOPES', implode(' ', array(
        \Google_Service_Calendar::CALENDAR)
));

class DefaultController extends Controller
{

    function getClient()
    {
        $client = new \Google_Client();
        $client->setApplicationName(APPLICATION_NAME);
        $client->setScopes(SCOPES);
        $client->setAuthConfigFile(CLIENT_SECRET_PATH);
        $client->setAccessType('offline');

        // Load previously authorized credentials from a file.
        $homeDirectory = getenv('HOME');
        if (empty($homeDirectory)) {
            $homeDirectory = getenv("HOMEDRIVE") . getenv("HOMEPATH");
        }
        $credentialsPath = str_replace('~', realpath($homeDirectory), CREDENTIALS_PATH);

        if (file_exists($credentialsPath)) {
            $accessToken = file_get_contents($credentialsPath);
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));

            // Exchange authorization code for an access token.
            $accessToken = $client->authenticate($authCode);

            // Store the credentials to disk.
            if (!file_exists(dirname($credentialsPath))) {
                mkdir(dirname($credentialsPath), 0700, true);
            }
            file_put_contents($credentialsPath, $accessToken);
            printf("Credentials saved to %s\n", $credentialsPath);
        }
        $client->setAccessToken($accessToken);

        // Refresh the token if it's expired.
        if ($client->isAccessTokenExpired()) {
            $client->refreshToken($client->getRefreshToken());
            file_put_contents($credentialsPath, $client->getAccessToken());
        }
        return $client;
    }

    /**
     * Expands the home directory alias '~' to the full path.
     * @param string $path the path to expand.
     * @return string the expanded path.
     */
    function expandHomeDirectory($path)
    {
        $homeDirectory = getenv('HOME');
        if (empty($homeDirectory)) {
            $homeDirectory = getenv("HOMEDRIVE") . getenv("HOMEPATH");
        }
        return str_replace('~', realpath($homeDirectory), $path);
    }

    /**
     * @Route("/", name="")
     *
     */
    function indexAction()
    {
        // Get the API client and construct the service object.
        $client = $this->getClient();
        $service = new \Google_Service_Calendar($client);

        // Print the next 10 events on the user's calendar.
        $calendarId = 'primary';
        $optParams = array(
            'maxResults' => 20,
            'orderBy' => 'startTime',
            'singleEvents' => TRUE,
            'timeMin' => date('c'),
        );
        $results = $service->events->listEvents($calendarId, $optParams);

        $currentDate = date("d/m/Y");
//        dump($results->getItems());

        $fillArray = $results->getItems();

        if (count($results->getItems()) == 0) {
            print "No upcoming events found.\n";
        } else {
            print "Upcoming events:\n";
            foreach ($results->getItems() as $event) {
                if (empty($start)) {
                    $start = $event->start->date;
                }
            }
        }

//        $event = new \Google_Service_Calendar_Event(array(
//            'summary' => 'Google I/O 2015',
//            'location' => '800 Howard St., San Francisco, CA 94103',
//            'description' => 'A chance to hear more about Google\'s developer products.',
//            'start' => array(
//                'dateTime' => '2015-05-28T09:00:00-07:00',
//                'timeZone' => 'America/Los_Angeles',
//            ),
//            'end' => array(
//                'dateTime' => '2015-05-28T17:00:00-07:00',
//                'timeZone' => 'America/Los_Angeles',
//            ),
//            'recurrence' => array(
//                'RRULE:FREQ=DAILY;COUNT=2'
//            ),
//            'attendees' => array(
//                array('email' => 'lpage@example.com'),
//                array('email' => 'sbrin@example.com'),
//            ),
//            'reminders' => array(
//                'useDefault' => FALSE,
//                'overrides' => array(
//                    array('method' => 'email', 'minutes' => 24 * 60),
//                    array('method' => 'popup', 'minutes' => 10),
//                ),
//            ),
//        ));

        $calendarId = 'primary';
//        $event = $service->events->insert($calendarId, $event);

        $events = $service->events->listEvents('primary');

//        $createDates = [];
//        $endDates = [];

        $myEvents = [];

        $number = 0;
        $completed = 0;
        $allCompleted = [];

        foreach ($results->getItems() as $event) {

            $number++;

            if (array_key_exists('end', $event['modelData'])) {
                {
                    if (array_key_exists('dateTime', $event['modelData']['end']) || array_key_exists('date', $event['modelData']['end']))

                        if (array_key_exists('dateTime', $event['modelData']['end'])) {
                            $endTimeStamp = substr($event['modelData']['end']['dateTime'], 0, 10);
                        }
                    if (array_key_exists('date', $event['modelData']['end'])) {
                        $endTimeStamp = substr($event['modelData']['end']['date'], 0, 10);
                    }

                    $startTimeStamp = substr($event->created, 0, 10);

                    $newStartdate = new \DateTime($startTimeStamp);
                    $newEnddate = new \DateTime($endTimeStamp);

                    $createDates[] = $newStartdate;
                    $endDates[] = $newEnddate;

                    $currentDate = new \DateTime();

                    $totalTime = $newStartdate->diff($newEnddate)->format("%d");
                    $difference = $currentDate->diff($newEnddate)->format("%d");

                    dump($event->summary);
                    dump($totalTime);
                    dump($difference);

                    if ($difference > 0 && $totalTime > 0) {
                        $completed = ($difference / $totalTime) * 100;
                    }

                    if ($completed > 100) {
                        $completed = 100;
                    }

                    dump($completed);

                }
            }

            $myEvents['event' . $number] = $event->summary;
            $myEvents['startDate' . $number] = $newStartdate;
            $myEvents['endDate' . $number] = $newEnddate;
            $myEvents['completed' . $number] = $completed;

//            $endTimeStamp = substr($event['modelData']['end']['dateTime'], 0, 10);

            $allCompleted[] = $completed;
        }

        // naam,
        // link naar trello,
        // slagingskans = $completed
        //einddatum = modelData->end->dateTime = newEndDate

        return $this->render('default/index.html.twig', array(
            'myEvents' => $results->getItems(),
            'myCompleted' => $allCompleted,
//            'endDate' => $endTimeStamp,
        ));
    }

}
