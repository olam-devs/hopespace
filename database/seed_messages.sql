-- Hope Space: 54 Sample Messages (9 categories × 3 formats × 2 languages)
-- All approved with published_at = NOW()

INSERT INTO `messages` (`language`, `category`, `format`, `content`, `status`, `created_at`, `published_at`) VALUES

-- 1. Life
('en', 'life', 'quote', 'Every day is a new chance to start again.', 'approved', NOW(), NOW()),
('sw', 'life', 'quote', 'Kila siku ni nafasi mpya ya kuanza upya.', 'approved', NOW(), NOW()),
('en', 'life', 'paragraph', 'Life can be heavy, but small steps forward matter. Even surviving today is a victory.', 'approved', NOW(), NOW()),
('sw', 'life', 'paragraph', 'Maisha yanaweza kuwa mazito, lakini hatua ndogo za mbele ni muhimu. Hata kuishi leo ni ushindi.', 'approved', NOW(), NOW()),
('en', 'life', 'lesson', 'I learned that patience and consistency make even the hardest days manageable.', 'approved', NOW(), NOW()),
('sw', 'life', 'lesson', 'Nimejifunza kuwa uvumilivu na uthabiti hufanya siku ngumu kuwa rahisi.', 'approved', NOW(), NOW()),

-- 2. Faith
('en', 'faith', 'quote', 'Faith gives hope when everything feels lost.', 'approved', NOW(), NOW()),
('sw', 'faith', 'quote', 'Imani hutoa tumaini wakati kila kitu kinaonekana kupotea.', 'approved', NOW(), NOW()),
('en', 'faith', 'paragraph', 'I found strength in praying daily. Even a few minutes help center the mind and calm the heart.', 'approved', NOW(), NOW()),
('sw', 'faith', 'paragraph', 'Nimepata nguvu kwa kuomba kila siku. Hata dakika chache husaidia kutulia akili na moyo.', 'approved', NOW(), NOW()),
('en', 'faith', 'lesson', 'I learned that trusting God doesn\'t remove problems, but it gives me courage to face them.', 'approved', NOW(), NOW()),
('sw', 'faith', 'lesson', 'Nimejifunza kuwa kumtumaini Mungu hakutoi matatizo, lakini kunanipa ujasiri wa kuyakabili.', 'approved', NOW(), NOW()),

-- 3. Education
('en', 'education', 'quote', 'Knowledge grows when shared with others.', 'approved', NOW(), NOW()),
('sw', 'education', 'quote', 'Maarifa hukua unapoyashirikisha na wengine.', 'approved', NOW(), NOW()),
('en', 'education', 'paragraph', 'Learning is not just about passing exams, but about understanding life and preparing for challenges.', 'approved', NOW(), NOW()),
('sw', 'education', 'paragraph', 'Kujifunza siyo tu kufaulu mitihani, bali kuelewa maisha na kujiandaa kwa changamoto.', 'approved', NOW(), NOW()),
('en', 'education', 'lesson', 'I realized that asking questions and seeking help improves understanding faster than struggling alone.', 'approved', NOW(), NOW()),
('sw', 'education', 'lesson', 'Nimegundua kuwa kuuliza maswali na kutafuta msaada kunarahisisha kuelewa kuliko kujikaza peke yangu.', 'approved', NOW(), NOW()),

-- 4. Family
('en', 'family', 'quote', 'Family is the anchor that keeps you grounded.', 'approved', NOW(), NOW()),
('sw', 'family', 'quote', 'Familia ni nguzo inayokushikilia chini.', 'approved', NOW(), NOW()),
('en', 'family', 'paragraph', 'I learned to appreciate small gestures: a call, a smile, or spending time together strengthens family bonds.', 'approved', NOW(), NOW()),
('sw', 'family', 'paragraph', 'Nimejifunza kuthamini vitendo vidogo: simu, tabasamu, au kutumia muda pamoja huimarisha uhusiano wa familia.', 'approved', NOW(), NOW()),
('en', 'family', 'lesson', 'Family is not perfect, but understanding and patience build lasting love.', 'approved', NOW(), NOW()),
('sw', 'family', 'lesson', 'Familia si kamilifu, lakini uelewa na uvumilivu huunda upendo wa kudumu.', 'approved', NOW(), NOW()),

-- 5. Finance
('en', 'finance', 'quote', 'Money is a tool, not the goal of life.', 'approved', NOW(), NOW()),
('sw', 'finance', 'quote', 'Pesa ni chombo, si lengo la maisha.', 'approved', NOW(), NOW()),
('en', 'finance', 'paragraph', 'I learned that budgeting and saving small amounts consistently leads to security and peace of mind.', 'approved', NOW(), NOW()),
('sw', 'finance', 'paragraph', 'Nimejifunza kuwa kupanga bajeti na kuweka akiba kidogo mara kwa mara huleta usalama na amani ya akili.', 'approved', NOW(), NOW()),
('en', 'finance', 'lesson', 'Financial discipline brings freedom, even if you don\'t earn a lot.', 'approved', NOW(), NOW()),
('sw', 'finance', 'lesson', 'Disiplin ya kifedha huleta uhuru, hata kama hujapata mengi.', 'approved', NOW(), NOW()),

-- 6. Encouragement
('en', 'encouragement', 'quote', 'You are stronger than you think.', 'approved', NOW(), NOW()),
('sw', 'encouragement', 'quote', 'Wewe ni shujaa zaidi ya unavyofikiria.', 'approved', NOW(), NOW()),
('en', 'encouragement', 'paragraph', 'Even when things seem impossible, small steps forward each day can make a difference.', 'approved', NOW(), NOW()),
('sw', 'encouragement', 'paragraph', 'Hata wakati mambo yanaonekana haywezekani, hatua ndogo za kila siku zinaweza kubadilisha hali.', 'approved', NOW(), NOW()),
('en', 'encouragement', 'lesson', 'I learned that believing in myself even when others doubt me is the first step to success.', 'approved', NOW(), NOW()),
('sw', 'encouragement', 'lesson', 'Nimejifunza kuwa kujiamini hata wengine wanaposhindwa kuamini, ni hatua ya kwanza ya mafanikio.', 'approved', NOW(), NOW()),

-- 7. Recovery
('en', 'recovery', 'quote', 'Healing is a journey, not a race.', 'approved', NOW(), NOW()),
('sw', 'recovery', 'quote', 'Kupona ni safari, si mbio.', 'approved', NOW(), NOW()),
('en', 'recovery', 'paragraph', 'I recovered by taking one day at a time, forgiving myself for mistakes, and seeking help when needed.', 'approved', NOW(), NOW()),
('sw', 'recovery', 'paragraph', 'Nilipona kwa kuchukua kila siku moja kwa wakati, kujisamehe kwa makosa, na kutafuta msaada nilipohitaji.', 'approved', NOW(), NOW()),
('en', 'recovery', 'lesson', 'Recovery taught me patience, resilience, and the value of asking for support.', 'approved', NOW(), NOW()),
('sw', 'recovery', 'lesson', 'Kupona kulinifundisha uvumilivu, uthabiti, na thamani ya kutafuta msaada.', 'approved', NOW(), NOW()),

-- 8. Marriage
('en', 'marriage', 'quote', 'Marriage grows stronger through understanding and communication.', 'approved', NOW(), NOW()),
('sw', 'marriage', 'quote', 'Ndoa huimarika kupitia uelewa na mawasiliano.', 'approved', NOW(), NOW()),
('en', 'marriage', 'paragraph', 'I learned that listening carefully to my partner, even during disagreements, prevents misunderstandings and strengthens love.', 'approved', NOW(), NOW()),
('sw', 'marriage', 'paragraph', 'Nimejifunza kuwa kusikiliza kwa makini mwenzi wangu, hata wakati wa tofauti, huzuia kutoelewana na huimarisha upendo.', 'approved', NOW(), NOW()),
('en', 'marriage', 'lesson', 'Compromise and empathy are the keys to a happy marriage.', 'approved', NOW(), NOW()),
('sw', 'marriage', 'lesson', 'Kukubalianea na huruma ni funguo la ndoa yenye furaha.', 'approved', NOW(), NOW()),

-- 9. Mental Health
('en', 'mental_health', 'quote', 'Taking care of your mind is as important as caring for your body.', 'approved', NOW(), NOW()),
('sw', 'mental_health', 'quote', 'Kujali akili yako ni muhimu kama kujali mwili wako.', 'approved', NOW(), NOW()),
('en', 'mental_health', 'paragraph', 'I realized that talking about emotions, even anonymously, reduces stress and builds resilience.', 'approved', NOW(), NOW()),
('sw', 'mental_health', 'paragraph', 'Nimegundua kuwa kuzungumza kuhusu hisia, hata kwa siri, hupunguza msongo wa mawazo na hujenga uthabiti.', 'approved', NOW(), NOW()),
('en', 'mental_health', 'lesson', 'Mental health is maintained through self-care, support, and asking for help without shame.', 'approved', NOW(), NOW()),
('sw', 'mental_health', 'lesson', 'Afya ya akili inahifadhiwa kupitia kujitunza, msaada, na kutafuta msaada bila aibu.', 'approved', NOW(), NOW());
