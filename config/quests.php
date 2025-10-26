<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Quest Definitions
    |--------------------------------------------------------------------------
    |
    | Definitions map quest codes to their objectives and rewards so the quest
    | log can render consistent copy without hard-coding values in the views.
    | Each objective or reward should define a "label" and optional metadata
    | like description, amount, or details.
    |
    */

    'definitions' => [
        'tutorial_01' => [
            'objectives' => [
                [
                    'label' => 'Start tutorial',
                    'description' => 'The tutorial explains the main features of the game and only takes a couple of minutes - start now!',
                ],
            ],
            'rewards' => [],
        ],
        'tutorial_02' => [
            'objectives' => [
                [
                    'label' => 'Close tutorial window',
                    'description' => 'Close the tutorial window',
                ],
                [
                    'label' => 'Open advisor',
                    'description' => 'Click on the advisor to open the task window',
                ],
                [
                    'label' => 'Disable help',
                    'description' => 'Disable the help feature',
                ],
            ],
            'rewards' => [
                [
                    'label' => 'A clay pit at level 1 is waiting for you!',
                    'details' => 'Now you can always get information about your current tasks. The next task will be activated once you have collected your reward. Claim your clay pit now!',
                ],
            ],
        ],
        'tutorial_03' => [
            'objectives' => [
                [
                    'label' => 'Open forest',
                    'description' => 'Open a forest field by clicking on it',
                ],
                [
                    'label' => 'Woodcutter 1',
                    'description' => 'Order the construction of a level 1 woodcutter',
                ],
            ],
            'rewards' => [
                [
                    'label' => 'Finish level 1 woodcutter immediately',
                    'details' => 'This is a good start on the way to greater economic power. I\'ll just finish the construction for you, so that we can continue.',
                ],
            ],
        ],
        'tutorial_04' => [
            'objectives' => [
                [
                    'label' => 'Open building',
                    'description' => 'Open the level 1 woodcutter',
                ],
                [
                    'label' => 'Woodcutter 2',
                    'description' => 'Order the construction of a level 2 woodcutter',
                ],
            ],
            'rewards' => [
                [
                    'label' => 'Finish construction of woodcutter level 2 immediately',
                    'details' => 'The display of your storage and stock can be found above your village. Construction costs will be taken from the stocks. I\'ll instantly finish the construction for you again.',
                ],
            ],
        ],
        'tutorial_05' => [
            'objectives' => [
                [
                    'label' => 'Open crop field',
                    'description' => 'Click on a crop field to open it',
                ],
                [
                    'label' => 'Cropland',
                    'description' => 'Upgrade the cropland to level 1',
                ],
            ],
            'rewards' => [
                [
                    'label' => 'Instantly finish construction of level 1 cropland and upgrade to level 2',
                    'details' => 'Your village now produces enough crop again to support new buildings. The population has to be fed locally, stationed troops can also be supported by crop deliveries.',
                ],
            ],
        ],
        'tutorial_06' => [
            'objectives' => [
                [
                    'label' => 'Hero image',
                    'description' => 'Click on the hero\'s image and open the overview',
                ],
                [
                    'label' => 'Hero production',
                    'description' => 'Change resources to clay and save',
                ],
            ],
            'rewards' => [
                [
                    'label' => 'Nicely done. Your hero can help you out in times of resource scarcity. All resources they produce will always go to their home village, even if they are underway. I will just increase your stock a little.',
                ],
            ],
        ],
        'tutorial_07' => [
            'objectives' => [
                [
                    'label' => 'Enter village',
                    'description' => 'Enter your village now.',
                ],
            ],
            'rewards' => [],
        ],
        'tutorial_08' => [
            'objectives' => [
                [
                    'label' => 'Construction menu',
                    'description' => 'Open the construction menu and select the infrastructure tab',
                ],
                [
                    'label' => 'Warehouse 1',
                    'description' => 'Order the construction of a level 1 warehouse',
                ],
            ],
            'rewards' => [
                [
                    'label' => 'One day Travian Plus',
                    'details' => 'Construction work has begun and soon you will have enough storage for your production and loot. I will give you one day of Travian PLUS, which allows you to queue a second construction order while the first one is still not finished.',
                ],
            ],
        ],
        'tutorial_09' => [
            'objectives' => [
                [
                    'label' => 'Click on building site',
                    'description' => 'Click on the rally point\'s building site',
                ],
                [
                    'label' => 'Rally Point',
                    'description' => 'Order the construction of a level 1 rally point',
                ],
            ],
            'rewards' => [
                [
                    'label' => 'Great! The construction has been ordered and your hero can now be sent on their way. For this task, I will give you some gold, which we will put to good use right away.',
                ],
            ],
        ],
        'tutorial_10' => [
            'objectives' => [
                [
                    'label' => 'Complete construction',
                    'description' => 'Complete construction orders immediately',
                ],
            ],
            'rewards' => [
                [
                    'label' => 'Now you can send your hero on an adventure. First, order the construction of some more resource fields so your village keeps on growing. Take this gold and spend it wisely.',
                ],
            ],
        ],
        'tutorial_11' => [
            'objectives' => [
                [
                    'label' => 'Hero adventure',
                    'description' => 'Send your hero on their first adventure',
                ],
            ],
            'rewards' => [
                [
                    'label' => 'Your hero immediately arrives at the adventure',
                    'details' => 'Great, your hero is on their way - what are they going to find? Below their image you can see the hero is underway. I will make them arrive now, so that we can see what happens.',
                ],
            ],
        ],
        'tutorial_12' => [
            'objectives' => [
                [
                    'label' => 'Report menu',
                    'description' => 'Open the report list',
                ],
                [
                    'label' => 'Read report',
                    'description' => 'Read the new adventure report',
                ],
            ],
            'rewards' => [
                [
                    'label' => 'You can already see what kind of bounty you got in the overview. What was it for you? Also, your hero got slightly injured - to deal with this, I will now give them some ointments.',
                ],
            ],
        ],
        'tutorial_13' => [
            'objectives' => [
                [
                    'label' => 'Hero inventory',
                    'description' => 'Click on your hero\'s image to open the inventory',
                ],
                [
                    'label' => 'Heal hero',
                    'description' => 'Click on the ointments in the inventory to use them',
                ],
            ],
            'rewards' => [
                [
                    'label' => 'Additionally, your hero receives 20 experience points.',
                    'details' => 'All items can be used this way. Depending on the item, it is either equipped or your hero consumes it. Further details can be found in any item\'s description.',
                ],
            ],
        ],
        'tutorial_14' => [
            'objectives' => [
                [
                    'label' => 'User Interface Help',
                    'description' => 'Open the user interface help and have a look around the UI',
                ],
            ],
            'rewards' => [
                [
                    'label' => 'In case you have a specific question, you can always check out our "Answers" first – and you will get help. For that, simply click on the \'i\' in the header of this window or in the top corner of the screen.',
                ],
            ],
        ],
        'tutorial_15' => [
            'objectives' => [
                [
                    'label' => 'End of tutorial',
                    'description' => 'End tutorial',
                ],
            ],
            'rewards' => [],
        ],
        'tutorial_15a' => [
            'objectives' => [],
            'rewards' => [
                [
                    'label' => 'Rally point, clay pit, woodcutter 2, cropland 2, 10 gold, 1 day PLUS',
                    'details' => 'To get you started, I will give you the buildings and advantages from the tutorial. Further tasks and rewards are waiting for you from now until you found your second village. Enjoy playing Travian!',
                ],
            ],
        ],
        'battle_01' => [
            'objectives' => [
                [
                    'label' => 'Move on to the second adventure',
                    'description' => 'During the tutorial, you already collected some experience from an adventure. Start the next adventure as soon as your hero has returned to your village. Loot and experience will allow you to grow more quickly.',
                ],
                [
                    'label' => 'Nice, your hero is already on their way. Hint: The more fighting strength your hero has, the less damage they will take from adventures.',
                ],
            ],
            'rewards' => [
                [
                    'label' => '30 hero experience',
                ],
            ],
        ],
        'battle_02' => [
            'objectives' => [
                [
                    'label' => 'Build a cranny in your village',
                    'description' => 'Many players live off of robbing the resources from other players. At game start, you have beginner\'s protection and you are safe. Construct a cranny to save at least a part of your resources from being plundered.',
                ],
                [
                    'label' => 'Great, now plunderers will not find it as easy to steal from you anymore. Check the info box to see the time of beginner\'s protection you have left.',
                ],
            ],
            'rewards' => [],
        ],
        'battle_03' => [
            'objectives' => [
                [
                    'label' => 'Construct barracks',
                    'description' => 'The barracks is the first building that allows you to train troops. Even as a peace-loving player, you will need troops in order to protect yourself and your allies from enemies.',
                ],
                [
                    'label' => 'Your barracks has been built! A good step towards world domination!',
                ],
            ],
            'rewards' => [],
        ],
        'battle_04' => [
            'objectives' => [
                [
                    'label' => 'Distribute your hero\'s attribute points after levelling up.',
                    'description' => 'Whenever your hero reaches a new level, they will get stronger. Open the hero\'s attributes and distribute the attribute points you have been awarded.',
                ],
                [
                    'label' => 'You can change the distribution of points for each attribute at any time. All you need for this is a book of wisdom, which can be found in adventures.',
                ],
            ],
            'rewards' => [],
        ],
        'battle_05' => [
            'objectives' => [
                [
                    'label' => 'Now it is time to train your first troops. In the barracks, you can already train one type of infantry unit.',
                ],
                [
                    'label' => 'The cornerstone for a glorious army has been laid! Always remember that you can still be attacked, even when you are not online.',
                ],
            ],
            'rewards' => [],
        ],
        'battle_07' => [
            'objectives' => [
                [
                    'label' => 'Open a free oasis on the map and attack it.',
                    'description' => 'Search the map for a free oasis nearby and plunder it. In case there are animals defending it, send your hero equipped with cages in order to capture them.',
                ],
                [
                    'label' => 'Congratulations, your first attack is on its way! You can still cancel it for a short period of time from within your rally point.',
                ],
            ],
            'rewards' => [
                [
                    'label' => '2 base-unit troops',
                ],
            ],
        ],
        'battle_08' => [
            'objectives' => [
                [
                    'label' => 'Finish 10 adventures',
                    'description' => 'Continue to send your hero on adventures. After having finished 10 of them, you can participate in auctions and trade items with other players.',
                ],
                [
                    'label' => 'Congratulations, you can now use the auction house. Take this silver, so you have some money for trading right away.',
                ],
            ],
            'rewards' => [
                [
                    'label' => '500 silver',
                ],
            ],
        ],
        'battle_09' => [
            'objectives' => [
                [
                    'label' => 'Create or place a bid in an auction.',
                    'description' => 'Go to the auction house and see which items are currently on offer. Maybe you want to turn some of your loot from adventures into silver as well?',
                ],
                [
                    'label' => 'Great, now you know how to trade equipment and consumable items with other players.',
                ],
            ],
            'rewards' => [],
        ],
        'battle_10' => [
            'objectives' => [
                [
                    'label' => 'Upgrade your barracks to level 3.',
                    'description' => 'Upgrade your barracks now. With this, you fulfill the requirements to unlock further buildings.',
                ],
                [
                    'label' => 'Nice. Your troops are now trained faster and you can construct an academy.',
                ],
            ],
            'rewards' => [],
        ],
        'battle_11' => [
            'objectives' => [
                [
                    'label' => 'Construct an academy now.',
                    'description' => 'New and stronger units for your village can be researched in the academy. Some units are very expensive and have high requirements before they can be researched.',
                ],
                [
                    'label' => 'Well done. Soon you will find out more about the soldiers of your tribe.',
                ],
            ],
            'rewards' => [],
        ],
        'battle_12' => [
            'objectives' => [
                [
                    'label' => 'Research an additional troop type.',
                    'description' => 'Check your research options now. There are infantry and cavalry units, as well as siege weapons. Units are mainly specialised in either attack or defence.',
                ],
                [
                    'label' => 'Research alone is of course not enough; your units will also need to be trained.',
                ],
            ],
            'rewards' => [],
        ],
        'battle_13' => [
            'objectives' => [
                [
                    'label' => 'Construct a smithy.',
                    'description' => 'A smithy allows you to better arm and equip your soldiers. Furthermore, a smithy is required in order to build additional troop buildings.',
                ],
                [
                    'label' => 'Perfect. Now you can better equip your soldiers.',
                ],
            ],
            'rewards' => [],
        ],
        'battle_14' => [
            'objectives' => [
                [
                    'label' => 'Research a unit improvement in the smithy.',
                    'description' => 'Improving your soldiers\' equipment isn\'t cheap. The more soldiers you have, the more rewarding an improvement will be. This time, you will gain more than a refund of the costs.',
                ],
                [
                    'label' => 'Perfect, now your troops\' ability to attack and defend has improved.',
                ],
            ],
            'rewards' => [],
        ],
        'battle_15' => [
            'objectives' => [
                [
                    'label' => 'Complete five adventures',
                    'description' => 'More Adventures mean more loot and experience. Keep your Hero active, but consider giving him some rest if his health is low.',
                ],
                [
                    'label' => 'Ointments can be used to heal your hero. If you equip ointment it will be used as soon as the hero got damage.',
                ],
            ],
            'rewards' => [
                [
                    'label' => '15 Ointments',
                ],
            ],
        ],
        'battle_16' => [
            'objectives' => [
                [
                    'label' => 'Found a second village using your settlers.',
                    'description' => 'In Travian: Rise of Alliances you start with six settlers and you can settle a new village right at the start of the game. Go to the map and find a close position to settle a new village.',
                ],
                [
                    'label' => 'You have three settlers left - with this resource award you can settle another village.',
                ],
            ],
            'rewards' => [],
        ],
        'economy_01' => [
            'objectives' => [
                [
                    'label' => 'Start the construction of an iron mine',
                    'description' => 'Order the construction of an iron mine! Your primary aim is still a high production of resources so that you can grow quickly.',
                ],
                [
                    'label' => 'Higher iron production for your village. A production bonus will help you increase the production of any particular resource even further.',
                ],
            ],
            'rewards' => [
                [
                    'label' => 'One day +25% bonus on the production of all resources',
                ],
            ],
        ],
        'economy_02' => [
            'objectives' => [
                [
                    'label' => 'Extend one more of each resource tile to level 1.',
                    'description' => 'Extend one lumber, clay, iron and crop field each to level 1. To complete this task you need to have at least 2 fields of each resource type above level 0. As long as Travian Plus is still active, you can always order one further construction at the same time.',
                ],
                [
                    'label' => 'Congratulations! Your village grows and thrives...',
                ],
            ],
            'rewards' => [],
        ],
        'economy_03' => [
            'objectives' => [
                [
                    'label' => 'Construct a granary',
                    'description' => 'In order to store more crop, you need a granary. Your current storage limit can be found when looking at the resources bar.',
                ],
                [
                    'label' => 'Nicely done! The granary now allows you to store more crop.',
                ],
            ],
            'rewards' => [],
        ],
        'economy_04' => [
            'objectives' => [
                [
                    'label' => 'Upgrade all resource fields to level 1',
                    'description' => 'In the beginning, it\'s best to focus on resources. Please upgrade all your resource fields to level 1.',
                ],
                [
                    'label' => 'Your resource production is developing nicely. Soon we can start the construction of more buildings in your village.',
                ],
            ],
            'rewards' => [],
        ],
        'economy_05' => [
            'objectives' => [
                [
                    'label' => 'Upgrade one resource field each to level 2',
                    'description' => 'Continue to increase your production. Upgrade one lumber, clay, iron and crop field each to level 2!',
                ],
                [
                    'label' => 'Well done! If you require more information regarding your production, click on your stocks.',
                ],
            ],
            'rewards' => [],
        ],
        'economy_06' => [
            'objectives' => [
                [
                    'label' => 'Construct marketplace',
                    'description' => 'In case you have a lack of one resource, you can trade it for other resources with other players at the marketplace. In order to construct a small marketplace, you need a larger main building.',
                ],
                [
                    'label' => 'Your marketplace is ready and you can now start trading with other players. Don\'t fall for the really bad offers though!',
                ],
            ],
            'rewards' => [],
        ],
        'economy_07' => [
            'objectives' => [
                [
                    'label' => 'Create a marketplace offer or accept one',
                    'description' => 'Existing offers on the marketplace can be seen when clicking on "buy". Check the exchange rate and the distance. Should you not be able to find a suitable offer, click on "offer" to create an offer yourself.',
                ],
                [
                    'label' => 'Awesome, you have initiated your first trade.',
                ],
            ],
            'rewards' => [],
        ],
        'economy_08' => [
            'objectives' => [
                [
                    'label' => 'Extend all resource fields to level 2',
                    'description' => 'Before you start constructing more expensive buildings, we should further increase your resource production. Upgrade all your resource fields to level 2.',
                ],
                [
                    'label' => 'Congratulations! Your resource production is developing nicely.',
                ],
            ],
            'rewards' => [],
        ],
        'economy_09' => [
            'objectives' => [
                [
                    'label' => 'Upgrade your warehouse to level 3',
                    'description' => 'It\'s time to adjust your warehouse to the increased production. Unplanned loot from your hero may also make your storage overflow.',
                ],
                [
                    'label' => 'Really good, no valuable resources will be wasted now.',
                ],
            ],
            'rewards' => [],
        ],
        'economy_10' => [
            'objectives' => [
                [
                    'label' => 'Upgrade your granary to level 3',
                    'description' => 'The higher your production, the easier your storage gets filled up. The granary should also be upgraded.',
                ],
                [
                    'label' => 'Now there is room again in the granary, so that production can continue even in your absence.',
                ],
            ],
            'rewards' => [],
        ],
        'economy_11' => [
            'objectives' => [
                [
                    'label' => 'Upgrade one cropland to level 5',
                    'description' => 'A grain mill increases the production of all your croplands. In order to be worth its price, you need to have a high enough base production.',
                ],
                [
                    'label' => 'Construct a level 1 grain mill',
                ],
                [
                    'label' => 'Now you have a lot of free crop available for further constructions. There are also buildings that increase the production of the other resources.',
                ],
            ],
            'rewards' => [],
        ],
        'economy_12' => [
            'objectives' => [
                [
                    'label' => 'Upgrade all resource fields to level 5',
                    'description' => 'You will need a much higher production in order to spare you a long waiting time until you are able to afford the buildings and settlers needed for a second village. Upgrade all resource fields to level 5.',
                ],
                [
                    'label' => 'Well done, you now have a decent production.',
                ],
            ],
            'rewards' => [
                [
                    'label' => 'One day +25% bonus to the production of all resources .',
                ],
            ],
        ],
        'world_01' => [
            'objectives' => [
                [
                    'label' => 'Open the statistics and compare yourself with other players.',
                    'description' => 'In the world of Travian, you compete against thousands of other players. Check the statistics to find out more about your own position in the game.',
                ],
                [
                    'label' => 'Apart from the rank, there is other useful information. The tab Top10 will show you the strongest attackers and the most successful robbers.',
                ],
            ],
            'rewards' => [],
        ],
        'world_02' => [
            'objectives' => [
                [
                    'label' => 'Change the village name on the village sign.',
                    'description' => 'A village name chosen by you is a sign to other players, showing them that your empire is being led actively.',
                ],
                [
                    'label' => 'Nice, now you have completed the first step to leave your mark in the world of Travian.',
                ],
            ],
            'rewards' => [],
        ],
        'world_03' => [
            'objectives' => [
                [
                    'label' => 'Upgrade your main building to level 3.',
                    'description' => 'A bigger main building unlocks new buildings and your workers\' speed will increase. Being able to build more quickly will however only pay out if you produce enough resources.',
                ],
                [
                    'label' => 'Great, the bigger main building now allows you to construct some additional buildings that you\'ve just unlocked.',
                ],
            ],
            'rewards' => [],
        ],
        'world_04' => [
            'objectives' => [
                [
                    'label' => 'Construct an embassy.',
                    'description' => 'The world of Travian is a dangerous place and you need to be able to defend yourself. The best additional defence is offered by strong allies. Construct an embassy in order to join an alliance.',
                ],
                [
                    'label' => 'Perfect, now you can accept alliance invitations. Invitations can be found inside the embassy.',
                ],
            ],
            'rewards' => [],
        ],
        'world_05' => [
            'objectives' => [
                [
                    'label' => 'Open the map in the menu.',
                    'description' => 'The map shows you the world of Travian. Check out your neighbours to find allies and identify threats.',
                ],
                [
                    'label' => 'Are there strong players or alliances near you? The map also helps you find oases and spots where you can settle new villages.',
                ],
            ],
            'rewards' => [],
        ],
        'world_06' => [
            'objectives' => [
                [
                    'label' => 'Open the messages overview and read the taskmaster\'s message!',
                    'description' => 'You have just received a message with some helpful hints. Unread messages can be identified by the number above the button. Have a look now.',
                ],
                [
                    'label' => 'Use messages to communicate with other players. It does always pay out to be calm and polite, even if you are at battle.',
                ],
            ],
            'rewards' => [],
        ],
        'world_07' => [
            'objectives' => [
                [
                    'label' => 'Go to the gold menu and claim the free gold from the taskmaster.',
                    'description' => 'I\'ve prepared a small gift for you at the gold shop. Collect a small package of free gold there.',
                ],
                [
                    'label' => 'That wasn\'t so hard, was it? Check "advantages" to find out more about what you can use your gold for.',
                ],
            ],
            'rewards' => [],
        ],
        'world_07a' => [
            'objectives' => [
                [
                    'label' => 'Go to the gold menu and claim the free gold from the taskmaster.',
                    'description' => 'I\'ve prepared a small gift for you at the gold shop. Collect a small package of free gold there.',
                ],
                [
                    'label' => 'That wasn\'t so hard, was it? Check "advantages" to find out more about what you can use your gold for.',
                ],
            ],
            'rewards' => [],
        ],
        'world_07b' => [
            'objectives' => [
                [
                    'label' => 'Take a look at the advantages you can buy with gold.',
                    'description' => 'During the tutorial, you\'ve already used gold to speed up your construction orders. In the gold shop, you can find out what else you can use your gold for.',
                ],
                [
                    'label' => 'Here is some free gold again, so that you can make use of some of the gold advantages.',
                ],
            ],
            'rewards' => [],
        ],
        'world_08' => [
            'objectives' => [
                [
                    'label' => 'Join an alliance.',
                    'description' => 'Search for allies and join an alliance. If you don\'t have any contacts yet, check the alliances of players near you or search for an alliance on the forum.',
                ],
                [
                    'label' => 'We\'re off to a great start. The stronger and more active each single player is, the stronger you will be as a team. Have you found out how to report attacks to each other and how to ask for assistance?',
                ],
            ],
            'rewards' => [],
        ],
        'world_09' => [
            'objectives' => [
                [
                    'label' => 'Upgrade your main building to level 5.',
                    'description' => 'It is time to upgrade the main building, so that you can construct new buildings. Please remember to also take care of your resource production at the same time.',
                ],
                [
                    'label' => 'Great, now you can construct a residence. Your workers\' speed has also improved.',
                ],
            ],
            'rewards' => [],
        ],
        'world_10' => [
            'objectives' => [
                [
                    'label' => 'Construct a residence or palace.',
                    'description' => 'Construct a seat of government now in order to found a new village soon. In case you are not sure if you want this village to remain your capital village, please select the residence.',
                ],
                [
                    'label' => 'This building is necessary in order to settle a new village or conquer one. Its level limits the amount of possible expansions.',
                ],
            ],
            'rewards' => [],
        ],
        'world_11' => [
            'objectives' => [
                [
                    'label' => 'Open the culture points tab in your residence or palace.',
                    'description' => 'In order to reign over more villages in your empire, you need culture points. The overview in the residence or palace tells you how far away you are and how long it is going to take.',
                ],
                [
                    'label' => 'In the village list you can also see the current status of possible new villages and the amount of missing culture points. Visit "Answers" to find out how to quickly increase your culture points.',
                ],
            ],
            'rewards' => [],
        ],
        'world_12' => [
            'objectives' => [
                [
                    'label' => 'Upgrade your warehouse to level 7.',
                    'description' => 'Upgrade your warehouse to prepare yourself for settling a new village. Your current storage capacity won\'t be enough soon to afford the required buildings and settlers.',
                ],
                [
                    'label' => 'Great, your storage capacity should be enough for some time now. Remember to defend or hide your valuable resources.',
                ],
            ],
            'rewards' => [],
        ],
        'world_13' => [
            'objectives' => [
                [
                    'label' => 'Open the reports and read the surroundings reports.',
                    'description' => 'The surroundings reports help you stay informed about events and changes within your neighbourhood.',
                ],
                [
                    'label' => 'From name changes to performed raids and conquerings, much is possible. I hope you enjoyed reading the reports.',
                ],
            ],
            'rewards' => [],
        ],
        'world_14' => [
            'objectives' => [
                [
                    'label' => 'Upgrade your residence or palace to level 10.',
                    'description' => 'Settlers can be trained in a palace or a residence. The tab "Train" shows you the required building level.',
                ],
                [
                    'label' => 'From each village you can only control 2 to 3 new villages. All that\'s missing for a new village now are 3 settlers and a lot of culture points.',
                ],
            ],
            'rewards' => [],
        ],
        'world_15' => [
            'objectives' => [
                [
                    'label' => 'Train three settlers.',
                    'description' => 'Settlers always travel in a small group when founding a new village. Protect your settlers well from attacks until they are ready to go.',
                ],
                [
                    'label' => 'Nice. Settlers will always take some resources for the new village with them, so they can start building it up right away.',
                ],
            ],
            'rewards' => [],
        ],
        'world_16' => [
            'objectives' => [
                [
                    'label' => 'Found a second village using your settlers.',
                    'description' => 'Search the map for a good spot to settle. Would you like it to be near your village, produce more of one particular resource or be near many oases?',
                ],
                [
                    'label' => 'Well done. I\'ll now give you another 2 days of Travian Plus - this will do you some good.',
                ],
            ],
            'rewards' => [],
        ],
    ],
];
