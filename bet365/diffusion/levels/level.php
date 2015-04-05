<?php

require_once __DIR__ . '/../connection.php';
require_once 'sportAA.php';
require_once 'sportEV.php';
require_once 'detailEV.php';

/*
 * Класс для хранения и организации иерхических дампов
 * Также умеет накладывать на себя дифф и уведомлять листенер о появлении новых событий
 * Есть методы для отрисовки всего дерева, только себя, все элементы от корня до текущего (путь без лишних строк)
 *
 */
class Bet365Level
{
    const AA = 'AA'; // искусственный уровень для обобщения всех дочерних под одной крышей
    const CL = 'CL';
    const CT = 'CT';
    const EV = 'EV';
    const MG = 'MG';
    const MA = 'MA';
    const CO = 'CO';
    const PA = 'PA';
    const ST = 'ST';
    const SG = 'SG';
    const TG = 'TG';
    const TE = 'TE';
    const ES = 'ES';
    const SC = 'SC';
    const SL = 'SL';

    const MESSAGE_CHUNK_DELIM = '|';
    const MESSAGE_VAR_DELIM   = ';';
    const MESSAGE_KV_DELIM    = '=';

    const MESSAGE_INITIAL = 'F';
    const MESSAGE_UPDATE  = 'U';
    const MESSAGE_DELETE  = 'D';
    const MESSAGE_INSERT  = 'I';

    static protected $levels = array( // hierarchy
        self::CL => array(),
        self::CT => array(self::CL),
        self::EV => array(self::CT, self::CL),
        self::MG => array(self::EV),
        self::MA => array(self::MG, self::EV),
        self::CO => array(self::MA),
        self::PA => array(self::CO, self::MA),
        self::TG => array(self::EV),
        self::TE => array(self::TG),
        self::SG => array(self::EV),
        self::ST => array(self::SG),
        self::ES => array(self::EV),
        self::SC => array(self::ES),
        self::SL => array(self::SC),
    );

    static protected $mappedSports = array(
        /*
        */
        1   => SportTypes::SPORT_SOCCER,
        //2   => horse racing,
        //3   => SportTypes::SPORT_CRICKET,
        //4   => greyhounds,
        //6   => lotto,
        8   => SportTypes::SPORT_RUGBY,          //Rugby Union,
        7   => SportTypes::SPORT_GOLF,
        9   => SportTypes::SPORT_BOX,
        10  => SportTypes::SPORT_FORMULA1,
        //11  => Athletics,
        12  => SportTypes::SPORT_FOOTBALL,
        13  => SportTypes::SPORT_TENNIS,
        14  => SportTypes::SPORT_SNOOKER,
        15  => SportTypes::SPORT_DARTS,
        16  => SportTypes::SPORT_BASEBALL,
        17  => SportTypes::SPORT_HOCKEY,
        18  => SportTypes::SPORT_BASKETBALL,
        19  => SportTypes::SPORT_RUGBY,          //Rugby League,
        27  => SportTypes::SPORT_AUTO_MOTOSPORT, //Motorbikes,
        35  => SportTypes::SPORT_BILLIARDS,      //Pool,
        36  => SportTypes::SPORT_AUSSIE_RULES,
        38  => SportTypes::SPORT_CYCLE_RACING,    //cycling
        //66  => bowls
        78  => SportTypes::SPORT_HANDBALL,
        //88  => Trotting,
        83  => SportTypes::SPORT_FUTSAL,
        84  => SportTypes::SPORT_FIELD_HOCKEY, //SPORT_BALL_HOCKEY,
        91  => SportTypes::SPORT_VOLLEYBALL,
        92  => SportTypes::SPORT_TABLE_TENNIS,
        94  => SportTypes::SPORT_BADMINTON,
        95  => SportTypes::SPORT_BEACH_VOLLEYBALL,
        98  => SportTypes::SPORT_CURLING,
        118 => SportTypes::SPORT_ALPINE_SKIING,
        119 => SportTypes::SPORT_BIATHLON,
        121 => SportTypes::SPORT_NORDIC_COMBINED,
        122 => SportTypes::SPORT_CROSS_COUNTRY,
        123 => SportTypes::SPORT_SKI_JUMPING,
        124 => SportTypes::SPORT_LUGE,
        125 => SportTypes::SPORT_SKATING,
        127 => SportTypes::SPORT_SKELETON,
        138 => SportTypes::SPORT_FREESTYLE,
        139 => SportTypes::SPORT_SNOWBOARD,
        //140 => poker,
        //147 => netball,
        //148 => surfing,
        /*
        */
    );


    protected $data = array();

    public function getTopic()
    {
        return isset($this->data['it']) ? $this->data['it'] : null;
    }

    public function getSport()
    {
        return isset($this->data['sport']) ? $this->data['sport'] : null;
    }

    public function getLevel()
    {
        return isset($this->data['level']) ? $this->data['level'] : null;
    }

    public function getParent()
    {
        return isset($this->data['parent']) ? $this->data['parent'] : null;
    }

    public function getHierarchy()
    {
        return isset($this->data['hierarchy']) ? $this->data['hierarchy'] : null;
    }

    public function getChildren()
    {
        return isset($this->data['sub']) ? $this->data['sub'] : null;
    }

    public function getChild($it)
    {
        if (isset($this->data['sub'][$it]))
        {
            return $this->data['sub'][$it];
        }
    }

    public function getVars()
    {
        return isset($this->data['vars']) ? $this->data['vars'] : null;
    }

    public function getVar($key)
    {
        return isset($this->data['vars'][$key]) ? $this->data['vars'][$key] : null;
    }

    public function getVarOR()
    {
        return isset($this->data['vars']['OR']) ? $this->data['vars']['OR'] : null;
    }

    public function getNumConn()
    {
        return isset($this->data['numConn']) ? $this->data['numConn'] : null;
    }

    public function getData($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    public function getParserResult()
    {
        return isset($this->data['parserResult']) ? $this->data['parserResult'] : null;
    }

    public function getEventKey()
    {
        return sprintf('%s|%s|%s', $this->getSport(), $this->getVar('CT'), $this->getVar('NA'));
    }

    public function getListener()
    {
        if (isset($this->data['listener'])) {
            return $this->data['listener'];
        }
        $obj = $this;
        while ($obj = $obj->getParent())
        {
            if ($listener = $obj->getListener()) {
                return $listener;
            }
        }
        throw new Exception('Need listener!');
    }

    public function setListener($listener)
    {
        $this->data['listener'] = $listener;
        return $this;
    }

    public function setParent($obj = null)
    {
        if ($obj instanceof Bet365Level) {
            $this->data['parent'] = $obj;
            $this->data['hierarchy'] = $obj->getHierarchy() + 1;
        } else {
            unset($this->data['parent']);
            $this->data['hierarchy'] = 0;
        }
        return $this;
    }

    public function setVarOR($value)
    {
        $this->data['vars']['OR'] = $value;
        return $this;
    }

    public function setData($arr)
    {
        foreach ($arr as $key => $value) {
            $this->data[$key] = $value;
        }
        $this->data['lastUpdated'] = time();
        return $this;
    }

    public function updateVars($arr)
    {
        if (! isset($this->data['vars'])) {
            $this->data['vars'] = array();
        }
        foreach ($arr as $key => $value) {
            $this->data['vars'][$key] = $value;
        }
        return $this;
    }

    public function setSport($value)
    {
        $this->data['sport'] = $value;
        return $this;
    }

    public function addChild(Bet365Level $obj)
    {
        $this->data['sub'][$obj->getTopic()] = $obj;
        $obj->setParent($this);
        return $this;
    }

    public function insertChild(Bet365Level $obj)
    {
        $min = 0;
        $max = -1;
        $ors = array();
        if ($this->data['sub'])
        {
            foreach ($this->data['sub'] as $topic => $child) {
                $ors[$topic] = $child->getVarOR();
            }
            $min = min($ors);
            $max = max($ors);

            if (is_null($min) || is_null($max))
            {
                $min = 0;
                $max = -1;
                foreach ($this->data['sub'] as $child)
                {
                    $max++;
                    $child->setVarOR($max);
                }
            }
        }

        $or = $obj->getVarOR();
        if (is_null($obj->getVarOR()))
        {
            $or = $max + 1;
            $obj->setVarOR($or);
        }

        if ($or >= $min && $or <= $max && $this->data['sub'])
        {
            $isAdded = false;
            $newChilds = array();
            foreach ($this->data['sub'] as $child)
            {
                if ($isAdded)
                {
                    $child->setVarOR($child->getvarOR() + 1);
                }
                elseif ($child->getVarOR() >= $or)
                {
                    $child->setVarOR($child->getvarOR() + 1);
                    $newChilds[$obj->getTopic()] = $obj;
                    $isAdded = true;
                }
                $newChilds[$child->getTopic()] = $child;
            }
            $this->data['sub'] = $newChilds;
        }
        elseif ($or < $min && $this->data['sub'])
        {
            $this->data['sub'] = array_merge(array($obj->getTopic() => $obj), $this->data['sub']);
        }
        else
        {
            $this->data['sub'][$obj->getTopic()] = $obj;
        }
        $obj->setParent($this);
        return $this;
    }

    public function removeChildren(array $topics)
    {
        $res = array();
        foreach ($this->data['sub'] as $topic => $obj)
        {
            if (in_array($topic, $topics)) {
                $obj->removeSelf();
                unset($obj);
            } else {
                $res[$obj->getTopic()] = $obj;
            }
        }
        $this->data['sub'] = $res;
        return $this;
    }

    protected function removeSelf()
    {
        return $this;
    }

    protected function removeRoot()
    {
        return $this;
    }

    public function setParserResult($value)
    {
        if (is_null($value)) {
            unset($this->data['parserResult']);
        } else {
            $this->data['parserResult'] = $value;
        }
        $this->data['lastUpdated'] = time();
        return $this;
    }

    public function cleanParserResultToRoot()
    {
        $obj = $this;
        while ($obj)
        {
            $obj->setParserResult(null);
            $obj = $obj->getParent();
        }
        return $this;
    }

    // ----------------------------- parse -----------------------------------

    static public function parseMessage($message, $context = null)
    {
        $message['rows'] = trim(str_replace('\n', '', $message['data']));

        if (strlen($message['rows']) < 3) {
            self::log('Message ' . $message['data'] . ' is too short');
            return;
        }

        $message['rows']   = explode(self::MESSAGE_CHUNK_DELIM, $message['rows']);

        $type = array_shift($message['rows']);
        if ($type == self::MESSAGE_INITIAL)
        {
            return self::parseTree($message);
        }
        elseif ($context)
        {
            if ($type == self::MESSAGE_INSERT)
            {
                $context->processInsert($message);
            }
            elseif ($type == self::MESSAGE_UPDATE)
            {
                $context->processUpdate($message);
            }
            elseif ($type == self::MESSAGE_DELETE)
            {
                $context->processDelete($message);
            }
            return $context;
        }
        self::log('Unknow type of message ' . $message['data']);
    }

    static protected function parseRow($row)
    {
        $res = array();
        foreach (explode(self::MESSAGE_VAR_DELIM, $row) as $i => $var)
        {
            $var = trim($var);
            if ($var)
            {
                $parts = explode(self::MESSAGE_KV_DELIM, $var);
                if (count($parts) == 1) {
                    $res[$parts[0]] = $parts[0];
                } else {
                    $res[$parts[0]] = $parts[1];
                }
            }
        }
        return $res;
    }

    static protected function parseTree($message)
    {
        $isSportDump = strpos($message['topic'], Bet365FlashDiffusionConnection::INITIAL_TOPIC) !== false;

        $message['topic'] = end($message['topics']);
        $root = new Bet365LevelSportAA();
        $root->setData(array(
            'it'        => $message['topic'],
            'level'     => self::AA,
            'vars'      => array('IT' => $message['topic']),
            'sub'       => array(),
            'numConn'   => $message['numConn'],
            'parserResult' => null,
        ));
        $root->setParent(null);
        $stack = array();

        foreach ($message['rows'] as $i => $row)
        {
            if (! trim($row)) {
                continue;
            }
            $parsedVars = explode(self::MESSAGE_VAR_DELIM, $row);
            $vars = array();
            $level = array_shift($parsedVars);
            if (! isset(self::$levels[$level])) {
                print_r($message['rows']);
                throw new Exception('Unknown level: ' . $level);
            }

            foreach ($parsedVars as $i => $var)
            {
                $var = trim($var);
                if ($var)
                {
                    list($name, $val) = explode(self::MESSAGE_KV_DELIM, $var);
                    $vars[$name] = $val;
                }
            }

            $parent = $root;
            foreach (self::$levels[$level] as $parentLevel)
            {
                if (isset($stack[$parentLevel]))
                {
                    $parent = $stack[$parentLevel];
                    break;
                }
            }

            if ($level == self::EV) {
                $obj = $isSportDump ? new Bet365LevelSportEV() : new Bet365LevelDetailEV();
            } else {
                $obj = new Bet365Level();
            }
            $obj->setData(array(
                'it'        => $vars['IT'],
                'level'     => $level,
                'vars'      => $vars,
                'sub'       => array(),
                'numConn'   => $message['numConn'],
                'parserResult' => null,
            ));
            $parent->addChild($obj);
            $stack[$obj->getLevel()] = $obj;

            // rewrite stack
            if ($obj->getHierarchy() !== count($stack))
            {
                $stack = array();
                $current = $obj;
                while ($current)
                {
                    $stack[$current->getLevel()] = $current;
                    $current = $current->getParent();
                }
            }
        }
        $last = end($root->getChildren());
        if (count($root->getChildren()) == 1 && $last->getTopic() == $root->getTopic()) {
            $last->setParent(null);
            return $last;
        } else {
            return $root;
        }
    }

    // --------------------------- diff -----------------------

    public function applyDiff($message)
    {
        self::parseMessage($message, $this);
    }

    protected function processInsert($message)
    {
        $myTopic = array_shift($message['topics']);
        if ($this->getTopic() !== $myTopic) {
            return $this->log(sprintf('Wrong topic %s to apply for dump %s', $myTopic, $this->getTopic()));
        }

        if (count($message['topics']) > 1)
        {
            $topic = reset($message['topics']);
            if (! $this->getChild($topic)) {
                $this->printDumpParents($this);
                return $this->log('INSERT ' . $myTopic . ' does not have ' . $topic);
            }
            $this->getChild($topic)->processInsert($message);
        }
        else
        {
            $dump = self::parseTree($message);
            $this->insertChild($dump);
            $this->cleanParserResultToRoot();
            if ($dump instanceof Bet365LevelSportEV) {
                $this->getListener()->addEvent($dump);
            }
        }
    }

    protected function processUpdate($message)
    {
        $myTopic = array_shift($message['topics']);
        if ($this->getTopic() !== $myTopic) {
            return $this->log(sprintf('Wrong topic %s to apply for dump %s', $myTopic, $this->getTopic()));
        }

        if (count($message['topics']) > 0)
        {
            $topic = reset($message['topics']);
            if (! $this->getChild($topic)) {
                $this->printDumpParents($this);
                return $this->log('UPDATE ' . $myTopic . ' does not have child "' . $topic . "'");
            }
            $this->getChild($topic)->processUpdate($message);
        }
        else
        {
            $this->updateVars(self::parseRow(array_shift($message['rows'])))->cleanParserResultToRoot();
        }
    }

    protected function processDelete($message)
    {
        $myTopic = array_shift($message['topics']);
        if ($this->getTopic() !== $myTopic) {
            return $this->log(sprintf('Wrong topic %s to apply for dump %s', $myTopic, $this->getTopic()));
        }

        $topic = reset($message['topics']);
        if (! $topic)
        {
            $this->removeRoot();
        }
        elseif (! $this->getChild($topic))
        {
            $this->printDumpParents($this);
            $this->log('DELETE ' . $myTopic . ' does not have ' . $topic);
        }
        elseif (count($message['topics']) > 1)
        {
            $this->getChild($topic)->processDelete($message);
        }
        else
        {
            $this->removeChildren($message['topics'])->cleanParserResultToRoot();
        }
    }

    // ------------------------- print -------------------------------

    public function printDumpFirstLevel()
    {
        $vars = array();
        foreach ($this->getVars() as $key => $val) {
            $vars[] = $key . '=' . $val;
        }
        echo '  ' . $this->getHierarchy() . '  ' . str_pad(' ', $this->getHierarchy() * 4) . $this->getLevel() . ';' . implode(';', $vars) . '   subs:' . count($this->getChildren()) . "\n";
    }

    public function printDump()
    {
        $this->printDumpFirstLevel();
        foreach ($this->getChildren() as $i => $sub) {
            $sub->printDump();
        }
    }

    public function getDumpFlat()
    {
        $res = '';
        if ($this->getLevel() !== self::AA)
        {
            $vars = array();
            foreach ($this->getVars() as $key => $val) {
                $vars[] = $key . '=' . $val;
            }
            $res = $this->getLevel() . ';' . implode(';', $vars) . self::MESSAGE_CHUNK_DELIM;
        }
        foreach ($this->getChildren() as $i => $sub) {
            $res .= $sub->getDumpFlat();
        }
        return $res;
    }

    public function printDumpParents()
    {
        $items = array();
        $obj = $this;
        while ($obj)
        {
            $items[] = $obj;
            $obj = $obj->getParent();
        }
        foreach (array_reverse($items) as $row) {
            $this->printDumpFirstLevel();
        }
    }

    public function log($message)
    {
        echo date('d.m.Y H:i:s') . ' Lev: ' . $message . PHP_EOL;
    }

    // ----------------------------- tests -------------------------

    public function checkOrdering()
    {
        $or = array();
        foreach ($this->getChildren() as $row)
        {
            $or[] = $row->getVarOR();
            $this->checkOrdering($row);
        }
        $sorted = $or;
        sort($sorted);
        if (count($or) !== count(array_unique($or)) || array_values($or) !== array_values($sorted))
        {
            echo "FAIL checkOrdering ---------------------------\n";
            $this->printDumpFirstLevel();
            foreach ($dump->sub as $row) {
                $row->printDumpFirstLevel();
            }
        }
    }
}
