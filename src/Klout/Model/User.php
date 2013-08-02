<?php

namespace Klout\Model;

use Klout;
use Klout\Model\Identity;
use Klout\Model\Score;
use Klout\Model\Topic;
use Klout\Collection\User as UserCollection;
use Klout\Collection\Topic as TopicCollection;
use Klout\Collection\Identity as IdentityCollection;

class User extends AbstractModel
{
    /* @var string - The user's Klout ID */
    protected $kloutId;

    /* @var Klout\Collection\Identity - An array of the user's identities */
    protected $identities;

    /* @var string - The user's nickname */
    protected $nickname;

    /* @var Klout\Model\Score - The user's score */
    protected $score;

    /* @var Klout\Collection\User - An array of users who influence this user */
    protected $influencers;

    /* @var Klout\Collection\User - An array of users who are influenced by this user */
    protected $influencees;

    /* @var Klout\Collection\Topic - An array of topics for this user */
    protected $topics;

    public function __construct(array $userData = null, array $influenceData = null, array $topicsData = null)
    {

        if (!empty($userData)) {
            $this->populate($userData, $influenceData, $topicsData);
        }
    }

    public function setKloutId($kloutId)
    {
        $this->kloutId = $kloutId;

        return $this;
    }

    public function getKloutId()
    {
        return $this->kloutId;
    }

    public function setIdentities(IdentityCollection $identities)
    {
        $this->identities = $identities;

        return $this;
    }

    public function getIdentities()
    {
        return $this->identities;
    }

    public function getIdentity($network = Klout::NETWORK_KLOUT)
    {
        return $this->identities[$network];
    }

    public function addIdentity(Klout\Model\Identity $identity)
    {
        $this->identities[$identity->getNetworkName()] = $identity;

        return $this;
    }

    public function getNickname()
    {
        return $this->nickname;
    }

    public function setNickname($nickname)
    {
        $this->nickname = $nickname;

        return $this;
    }

    public function getScore()
    {
        return $this->score;
    }

    public function setScore(Klout\Model\Score $score)
    {
        $this->score = $score;

        return $this;
    }

    public function getInfluencers()
    {
        return $this->influencers;
    }

    public function setInfluencers(UserCollection $influencers)
    {
        $this->influencers = $influencers;

        return $this;
    }

    public function getInfluencees()
    {
        return $this->influencees;
    }

    public function setInfluencees(UserCollection $influencees)
    {
        $this->influencees = $influencees;

        return $this;
    }

    public function getTopics()
    {
        return $this->topics;
    }

    public function setTopics(TopicCollection $topics)
    {
        $this->topics = $topics;

        return $this;
    }

    public function populate(array $userData, array $influenceData = null, array $topicsData = null)
    {

        $this->setKloutId($userData['kloutId']);
        $this->setNickname($userData['nick']);

        $score = new Score();
        $scoreData = array();
        if (!empty($userData['score'])) {
            // Need to change the score data becauce the call to user.json
            // will return the 'score' as an array but the user.json/score call
            // will return the score data in a different format
            $scoreData = array(
                'score' => $userData['score']['score'],
                'bucket' => $userData['score']['bucket'],
                'scoreDeltas' => $userData['scoreDeltas'],
            );
            $score->populate($userData['kloutId'], $scoreData);
        }
        $this->setScore($score);

        if (!empty($influenceData)) {
            $influencersData = array();
            foreach ($influenceData['myInfluencers'] as $value) {
                $influencersData[] = array('userData' => $value['entity']['payload']);
            }
            $influencers = self::createUserCollection($influencersData);

            $influenceesData = array();
            foreach ($influenceData['myInfluencees'] as $value) {
                $influenceesData[] = array('userData' => $value['entity']['payload']);
            }
            $influencees = self::createUserCollection($influenceesData);
        } else {
            $influencers = new UserCollection();
            $influencees = new UserCollection();
        }

        $this->setInfluencers($influencers);
        $this->setInfluencees($influencees);

        $topics = Topic::createTopicCollection($topicsData);
        $this->setTopics($topics);

        return $this;
    }

    public static function createUserCollection(array $userArray)
    {
        $users = new UserCollection();
        if (empty($userArray)) {
            return $users;
        }

        foreach ($userArray as $userData) {
            if (!is_array($userData) || empty($userData['userData'])) {
                throw new InvalidArgumentException('Invalid user data.');
            }

            // Need to init the array even if empty to avoid errors later
            if (!isset($userData['influenceData'])) {
                $userData['influenceData'] = array();
            }
            if (!isset($userData['topicsData'])) {
                $userData['topicsData'] = array();
            }
            $user = new self($userData['userData'], $userData['influenceData'], $userData['topicsData']);
            if (!$user->getKloutId()) {
                throw new InvalidArgumentException('Invalid user data.');
            }
            $users[(string)$user->getKloutId()] = $user;
        }

        return $users;
    }
}