-- ============================================================
-- HopeSpace Seed Data — Compelling Original Content
-- Author credit: HopeSpace Admin
-- Run AFTER full_migration.sql
-- Safe to re-run with INSERT IGNORE on unique rows.
-- ============================================================

-- ============================================================
-- 1. HOPESPACE ADMIN AUTHOR ACCOUNT
-- ============================================================
INSERT IGNORE INTO `users` (
    `username`, `email`, `password_hash`, `full_name`,
    `is_reader`, `is_author`, `language_preference`, `is_active`
) VALUES (
    'hopespace_admin',
    'content@hopespace.olamtec.co.tz',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'HopeSpace Admin',
    1, 1, 'en', 1
);

INSERT IGNORE INTO `user_profiles` (`user_id`, `bio`, `avatar_type`)
SELECT `id`,
       'Official HopeSpace content team. Sharing verified stories, quotes and testimonies of hope, resilience and transformation from East Africa.',
       'generated'
FROM `users` WHERE `username` = 'hopespace_admin';

-- ============================================================
-- 2. MESSAGES — All 9 categories × 4 formats × 2 languages
-- ============================================================

INSERT INTO `messages` (`language`, `category`, `format`, `content`, `status`, `created_at`, `published_at`) VALUES

-- ═══════════════════════════════════════════════
-- LIFE
-- ═══════════════════════════════════════════════
('en', 'life', 'quote',
 'You are not the worst thing that ever happened to you. You are the person who survived it — and that is an entirely different story.',
 'approved', NOW(), NOW()),

('sw', 'life', 'quote',
 'Wewe si kitu kibaya zaidi kilichokupata. Wewe ni mtu aliyenusurika — na hiyo ni hadithi tofauti kabisa.',
 'approved', NOW(), NOW()),

('en', 'life', 'paragraph',
 'There is a moment in every difficult season when you look around and realize the life you imagined is nowhere near the life you are living. That gap can feel like failure. But it is not. It is the space where character is built — slowly, quietly, without applause. The most meaningful lives are rarely the ones that went according to plan. They are the ones that were rebuilt, rerouted, and refined by seasons that were never requested. Keep going. You are not behind. You are being shaped.',
 'approved', NOW(), NOW()),

('sw', 'life', 'paragraph',
 'Kuna wakati katika kila kipindi kigumu ambapo unatazama pande zote na kutambua kwamba maisha uliyoyafikiria hayako karibu na maisha unayoyaishi. Pengo hilo linaweza kuhisi kama kushindwa. Lakini si hivyo. Ni nafasi ambapo tabia inajengwa — polepole, kimya kimya, bila makofi ya kushangilia. Maisha yenye maana zaidi mara nyingi si yale yaliyokwenda kwa mpango. Ni yale yaliyojengwa upya, kubadilishiwa njia, na kusafishwa na majira ambayo hayakuombwa. Endelea. Hukuchelewa. Unajengwa.',
 'approved', NOW(), NOW()),

('en', 'life', 'lesson',
 'I used to chase the version of life I thought I deserved. Big city, big title, big income. I chased it across three countries and two failed businesses before I stopped long enough to ask: what do I actually want? The answer was quieter than I expected — peace, purpose, and to matter to a few people deeply. That realization did not come in a success. It came in a breakdown at 3am in a city where nobody knew my name. Breakdowns, I have learned, are often breakthroughs in disguise. — HopeSpace Admin',
 'approved', NOW(), NOW()),

('sw', 'life', 'lesson',
 'Nilikuwa nikifuatilia toleo la maisha niliyofikiri nilistahili. Mji mkubwa, cheo kikubwa, mapato makubwa. Nilikifuatilia katika nchi tatu na biashara mbili zilizoshindwa kabla ya kusimama na kuuliza: nataka nini kweli kweli? Jibu lilikuwa kimya zaidi kuliko nilivyotegemea — amani, kusudi, na kumhusisha mtu fulani kwa undani. Kutambua huko hakukuja katika mafanikio. Kulikuja katika kuvunjika usiku wa manane katika mji ambapo hakuna aliyejua jina langu. Kuvunjika, nimejifunza, mara nyingi ni kufunguka kwa mafanikio kilichojificha. — HopeSpace Admin',
 'approved', NOW(), NOW()),

('en', 'life', 'question',
 'If you could go back and tell your younger self one truth that would have saved you years of pain — what would it be?',
 'approved', NOW(), NOW()),

('sw', 'life', 'question',
 'Kama ungeweza kurudi nyuma na kumwambia nafsi yako ya mdogo ukweli mmoja ambao ungeokoa miaka ya maumivu — ungekuwa nini?',
 'approved', NOW(), NOW()),

-- ═══════════════════════════════════════════════
-- FAITH
-- ═══════════════════════════════════════════════
('en', 'faith', 'quote',
 'Faith is not a feeling. It is a decision you make every morning to keep walking before you can see where you are going.',
 'approved', NOW(), NOW()),

('sw', 'faith', 'quote',
 'Imani si hisia. Ni uamuzi unaofanya kila asubuhi kuendelea kutembea kabla hujaona unakokwenda.',
 'approved', NOW(), NOW()),

('en', 'faith', 'paragraph',
 'Nobody talks honestly about how dark the middle of a faith journey can get. People share the miracle — the healing, the breakthrough, the prayer finally answered. What they rarely share is the two years between the prayer and the answer. The sleepless nights. The silence that felt like abandonment. The moments when the belief you built your whole life on started to feel like sand. That middle place — that desert between the promise and the fulfilment — is where real faith is actually forged. If you are in the middle right now, you have not been forgotten. You are being formed.',
 'approved', NOW(), NOW()),

('sw', 'faith', 'paragraph',
 'Hakuna anayezungumza wazi kuhusu giza la njiani katika safari ya imani. Watu wanashiriki muujiza — uponyaji, mafanikio, sala iliyojibiwa hatimaye. Wanachokishiriki kidogo ni miaka miwili kati ya sala na jibu. Usiku bila usingizi. Ukimya uliohisi kama kuachwa. Nyakati ambapo imani uliyoijenga maisha yako yote yote ilianza kuhisi kama mchanga. Mahali pale pa kati — jangwa kati ya ahadi na utimilifu — ndipo imani halisi inapoundwa. Kama uko katikati sasa hivi, hukusahauliwa. Unajengwa.',
 'approved', NOW(), NOW()),

('en', 'faith', 'lesson',
 'The hardest thing my faith ever asked of me was to let go of control over an outcome I had prayed about for years. I kept holding on because letting go felt like giving up. But I had confused surrender with defeat. The day I opened my hands and said "I trust You even if this does not go the way I want" — something shifted. Not immediately in my circumstances. First, it shifted in me. That internal shift is where everything else begins. — HopeSpace Admin',
 'approved', NOW(), NOW()),

('sw', 'faith', 'lesson',
 'Jambo gumu zaidi imani yangu ilioniliza kufanya ni kuacha udhibiti wa matokeo niliyokuwa nikiomba kwa miaka. Niliendelea kushika kwa sababu kuacha kulihisi kama kukata tamaa. Lakini nilikuwa nimechanganya kujisalimisha na kushindwa. Siku nilipoifungua mikono yangu na kusema "Nakuamini hata kama hili halikwendi jinsi ninavyotaka" — kitu kilibadilika. Si katika mazingira yangu mara moja. Kwanza, sasa ndani yangu. Mabadiliko ya ndani ndilo mahali ambapo kila kitu kingine huanza. — HopeSpace Admin',
 'approved', NOW(), NOW()),

('en', 'faith', 'question',
 'What has kept you holding on during a season when every sign seemed to say it was time to give up?',
 'approved', NOW(), NOW()),

('sw', 'faith', 'question',
 'Ni nini kilichokushikilia katika kipindi ambapo kila ishara ilionekana kusema ni wakati wa kuacha?',
 'approved', NOW(), NOW()),

-- ═══════════════════════════════════════════════
-- EDUCATION
-- ═══════════════════════════════════════════════
('en', 'education', 'quote',
 'The classroom is not the only place where the mind grows. Every experience you survive, every failure you analyse, every book that keeps you reading past midnight — that is your real education.',
 'approved', NOW(), NOW()),

('sw', 'education', 'quote',
 'Darasa si mahali pekee ambapo akili inakua. Kila uzoefu unaoushinda, kila kushindwa unakochambua, kila kitabu kinachokukaa ukisoma usiku wa manane — hiyo ndiyo elimu yako ya kweli.',
 'approved', NOW(), NOW()),

('en', 'education', 'paragraph',
 'There is a lie that circulates quietly through many communities: that education is a luxury, that it belongs to those with money and connections, that if you missed your window it is gone forever. That lie has stolen futures from millions of brilliant people. The truth is this — the desire to learn, kept alive and acted on consistently, is the seed of every educated person who ever changed anything. Libraries are free. The internet is accessible. Mentors want to be found. The hunger matters more than the institution.',
 'approved', NOW(), NOW()),

('sw', 'education', 'paragraph',
 'Kuna uongo unaozunguka kimya kimya katika jamii nyingi: kwamba elimu ni anasa, kwamba ni ya wenye pesa na misimamizi, kwamba ukikosa nafasi yako imeenda milele. Uongo huo umeibia siku zijazo za watu wengi wenye akili. Ukweli ni huu — tamaa ya kujifunza, ikishikwa hai na kutendeka kwa uthabiti, ndiyo mbegu ya kila mtu aliyeelimika aliyewahi kubadilisha kitu chochote. Maktaba ni bure. Intaneti inafikiwa. Washauri wanataka kupatikana. Njaa ya kujua ndiyo inayohusika zaidi kuliko taasisi.',
 'approved', NOW(), NOW()),

('en', 'education', 'lesson',
 'I failed my national exams twice. The second time, I was twenty-two years old and everyone around me had already moved on. I remember sitting with those results and feeling like a door had not just closed — it had been locked from the outside. Three years later, I had taught myself web development through free online courses, built a product, and was employed at a company that never asked about my exam scores. The lesson is not that exams do not matter. It is that they are one door — and a life is a building with many. — HopeSpace Admin',
 'approved', NOW(), NOW()),

('sw', 'education', 'lesson',
 'Nilifeli mitihani ya taifa mara mbili. Mara ya pili nilikuwa na miaka ishirini na miwili na kila mtu karibu nami alikuwa ameshaendelea. Nakumbuka kukaa na matokeo hayo nikihisi mlango haukufungwa tu — ulifungwa kutoka nje. Miaka mitatu baadaye, nilikuwa nimejifundisha utengenezaji wa wavuti kupitia kozi za mtandaoni za bure, nilijengea bidhaa, na nilikuwa nimeajiriwa na kampuni ambayo haikuuliza kamwe kuhusu alama zangu za mtihani. Somo si kwamba mitihani haisaidii. Ni kwamba ni mlango mmoja — na maisha ni jengo lenye mingi. — HopeSpace Admin',
 'approved', NOW(), NOW()),

('en', 'education', 'question',
 'What is something you have taught yourself — outside of school — that ended up mattering more than anything you learned in a classroom?',
 'approved', NOW(), NOW()),

('sw', 'education', 'question',
 'Kuna kitu gani ulichojifundisha mwenyewe — nje ya shule — ambacho mwishowe kilihusika zaidi kuliko chochote ulichojifunza darasani?',
 'approved', NOW(), NOW()),

-- ═══════════════════════════════════════════════
-- ENCOURAGEMENT
-- ═══════════════════════════════════════════════
('en', 'encouragement', 'quote',
 'The fact that you are still here — still asking questions, still trying, still breathing — is not nothing. It is everything.',
 'approved', NOW(), NOW()),

('sw', 'encouragement', 'quote',
 'Ukweli kwamba bado uko hapa — bado unauliza maswali, bado unajaribu, bado unapumua — si kitu kidogo. Ni kila kitu.',
 'approved', NOW(), NOW()),

('en', 'encouragement', 'paragraph',
 'You are allowed to be exhausted. You are allowed to have a day where you cannot see the point, where the weight of everything feels unbearable, where getting out of bed feels like the hardest thing in the world. That is not weakness — that is what carrying too much for too long looks like. What matters is not that you feel strong right now. What matters is that you do not let that moment become your conclusion. Rest if you need to. Fall apart if you have to. But do not write a permanent ending from a temporary place.',
 'approved', NOW(), NOW()),

('sw', 'encouragement', 'paragraph',
 'Unaruhusiwa kuwa umechoka. Unaruhusiwa kuwa na siku ambapo una uona maana, ambapo uzito wa kila kitu unahisi usiovumilika, ambapo kutoka kitandani kunahisi kama kitu kigumu zaidi duniani. Hii si udhaifu — hii ndivyo kubeba mzigo mwingi kwa muda mrefu unavyoonekana. Kinachohusika si kwamba unahisi nguvu sasa hivi. Kinachohusika ni kwamba usimruhusu wakati huo kuwa hitimisho lako. Pumzika ukihitaji. Vunjika ukibidi. Lakini usiandike mwisho wa kudumu kutoka mahali pa muda.',
 'approved', NOW(), NOW()),

('en', 'encouragement', 'lesson',
 'I have a friend who called me on what she described as her worst night. She did not say much — she just needed to know someone was on the other end of the phone. I stayed on the line for two hours. Years later she told me that call is the reason she is still alive. I had no special training. I just showed up and stayed. Sometimes the most powerful thing you can offer someone is your presence — uncalculated, unhurried, and without an agenda. — HopeSpace Admin',
 'approved', NOW(), NOW()),

('sw', 'encouragement', 'lesson',
 'Nina rafiki aliyenipiga simu usiku ambao aliuita usiku wake mbaya zaidi. Alisema kidogo — alihitaji tu kujua mtu yupo upande mwingine wa simu. Nilikaa mtandaoni kwa masaa mawili. Miaka baadaye aliniambia simu hiyo ndiyo sababu bado yuko hai. Sikuwa na mafunzo maalum. Nilijitokeza tu na kukaa. Wakati mwingine kitu chenye nguvu zaidi unachoweza kumpa mtu ni uwepo wako — usio na hesabu, usio na haraka, na bila mpangilio. — HopeSpace Admin',
 'approved', NOW(), NOW()),

('en', 'encouragement', 'question',
 'Who in your life showed up for you during your hardest moment — and have you ever told them what that meant to you?',
 'approved', NOW(), NOW()),

('sw', 'encouragement', 'question',
 'Ni nani katika maisha yako aliyejitokeza kwako katika wakati wako mgumu zaidi — na umewahi kumwambia maana yake kwako?',
 'approved', NOW(), NOW()),

-- ═══════════════════════════════════════════════
-- MARRIAGE
-- ═══════════════════════════════════════════════
('en', 'marriage', 'quote',
 'A great marriage is not built on never arguing. It is built on two people who keep choosing to come back to the table — even when walking out would be easier.',
 'approved', NOW(), NOW()),

('sw', 'marriage', 'quote',
 'Ndoa nzuri haikujengwa kwa kutokugombana kamwe. Imejengwa na watu wawili ambao wanaendelea kuchagua kurudi mezani — hata wakati kutoka kungelikuwa rahisi.',
 'approved', NOW(), NOW()),

('en', 'marriage', 'paragraph',
 'Nobody tells you that the most dangerous season in a marriage is not the explosive one — it is the quiet one. The years when you stop having real conversations. When you share a bed but not a life. When you have become two people living in polite parallel. That silence, left unaddressed, becomes a canyon. Every distant marriage I have seen was not destroyed by a single betrayal — it was eroded by a thousand moments of choosing convenience over connection. Speak up while there is still time. Ask the hard question. Say the uncomfortable thing. Silence is not peace. It is just postponed pain.',
 'approved', NOW(), NOW()),

('sw', 'marriage', 'paragraph',
 'Hakuna anayekuambia kwamba kipindi cha hatari zaidi katika ndoa si kile cha mlipuko — ni kile cha ukimya. Miaka ambapo unacha mazungumzo ya kweli. Unaposhiriki kitanda lakini si maisha. Unapokuwa watu wawili mnaoishi sambamba kwa adabu. Ukimya huo, ukiaachwa bila kushughulikiwa, unakuwa bonde. Kila ndoa ya mbali niliyoiona haikuangushwa na usaliti mmoja — ilikatwa kidogo kidogo na nyakati elfu za kuchagua urahisi badala ya muunganiko. Zungumza wakati bado kuna muda. Uliza swali gumu. Sema kitu kisichostarehe. Ukimya si amani. Ni maumivu yaliyoahirishwa tu.',
 'approved', NOW(), NOW()),

('en', 'marriage', 'lesson',
 'My husband and I nearly divorced in year six. Not because of anything dramatic — because of nothing. We had stopped investing in each other and the relationship had slowly gone hollow. What saved us was not a counsellor or a miracle. It was a decision we made on an ordinary Tuesday to start having dinner together without phones, and to ask each other one real question every night. In a year, we were unrecognisable from the couple that had been drifting. Small consistent acts of attention rebuilt what years of neglect had eroded. — HopeSpace Admin',
 'approved', NOW(), NOW()),

('sw', 'marriage', 'lesson',
 'Mume wangu na mimi tulikaribia talaka mwaka wa sita. Si kwa sababu ya kitu cha kushangaza — kwa ajili ya kitu. Tulikuwa tumeacha kuwekeza katika kila mmoja wetu na uhusiano ulianza kuwa tupu polepole. Kilichotuokoa haikuwa mshauri wala muujiza. Ilikuwa uamuzi tuliofanya Jumanne moja ya kawaida ya kuanza kula chakula cha jioni pamoja bila simu, na kuulizana swali moja halisi kila usiku. Katika mwaka mmoja, tulikuwa hatutambulikani kutoka kwa wanandoa waliokuwa wakitawanyika. Matendo madogo ya makini kwa uthabiti yalijenga upya kile ambacho miaka ya kupuuzia ilikuwa imekata kidogo kidogo. — HopeSpace Admin',
 'approved', NOW(), NOW()),

('en', 'marriage', 'question',
 'What is the one conversation you and your partner have been avoiding — and what do you think would happen if you finally had it?',
 'approved', NOW(), NOW()),

('sw', 'marriage', 'question',
 'Ni mazungumzo gani unayoyaepuka wewe na mwenzako — na unafikiri nini kingetokea kama mwishowe mngeyafanya?',
 'approved', NOW(), NOW()),

-- ═══════════════════════════════════════════════
-- MENTAL HEALTH
-- ═══════════════════════════════════════════════
('en', 'mental_health', 'quote',
 'Healing does not mean the damage never existed. It means the damage no longer controls your direction.',
 'approved', NOW(), NOW()),

('sw', 'mental_health', 'quote',
 'Kupona haimaanishi kwamba uharibifu haukutokea kamwe. Inamaanisha kwamba uharibifu hauendelei kuthibiti mwelekeo wako.',
 'approved', NOW(), NOW()),

('en', 'mental_health', 'paragraph',
 'In many communities across East Africa, talking about what is happening inside your mind is still treated as a sign of madness or moral failure. So people go quiet. They smile through breakdowns. They carry invisible weight for years until the body finally says enough — through illness, through anger, through numbness, through disappearance. Mental health is not a Western concept. Loss, grief, anxiety, trauma, despair — these are human experiences that have always existed here. What is new is that we now have language for them. And language is the beginning of healing.',
 'approved', NOW(), NOW()),

('sw', 'mental_health', 'paragraph',
 'Katika jamii nyingi Afrika Mashariki, kuzungumza kuhusu kinachoendelea ndani ya akili yako bado kunachukuiwa kama ishara ya wazimu au kushindwa kwa moyo. Kwa hivyo watu wananyamaza kimya. Wanatabasamu katika kuvunjika kwao. Wanabeba mzigo usiooonekana kwa miaka hadi mwili hatimaye unasema kinatosha — kupitia ugonjwa, kupitia hasira, kupitia kukosa hisia, kupitia kutoweka. Afya ya akili si dhana ya Kimagharibi. Hasara, majonzi, wasiwasi, msongo, kukata tamaa — hizi ni uzoefu wa kibinadamu ambazo zimekuwepo hapa miaka yote. Kipya ni kwamba sasa tuna lugha yake. Na lugha ni mwanzo wa kupona.',
 'approved', NOW(), NOW()),

('en', 'mental_health', 'lesson',
 'I spent four years heavily medicated for depression before I found a therapist who finally helped me understand where it came from. Those four years were not wasted — the medication kept me functional. But the real work began when I started talking. I learned that depression is rarely just a chemical imbalance. For me it was also grief I had never processed, patterns I had inherited, and beliefs about myself I had never questioned. Understanding the roots did not cure me overnight. But it gave me something medication alone never could: agency. — HopeSpace Admin',
 'approved', NOW(), NOW()),

('sw', 'mental_health', 'lesson',
 'Nilipitisha miaka minne nikitumia dawa nyingi kwa unyogovu kabla ya kupata mtaalamu wa maumivu ya akili aliyonisaidia hatimaye kuelewa chanzo chake. Miaka ile minne haikupotea — dawa zilinifanya niendelee kufanya kazi. Lakini kazi halisi ilianza nilipokuwa nikianza kuzungumza. Nilijifunza kwamba unyogovu mara nyingi si uharibifu wa kemikali peke yake. Kwangu ilikuwa pia majonzi ambayo sikuyashughulikia kamwe, mifumo niliyoirithi, na imani kuhusu nafsi yangu ambazo sikuzihoji kamwe. Kuelewa mizizi haikunitibu usiku mmoja. Lakini ilinipatia kitu ambacho dawa peke yake hazikuwahi kuweza: nguvu ya kuchagua. — HopeSpace Admin',
 'approved', NOW(), NOW()),

('en', 'mental_health', 'question',
 'What is one thing you do — or have done — to protect your peace when the world around you feels like too much?',
 'approved', NOW(), NOW()),

('sw', 'mental_health', 'question',
 'Ni kitu kimoja unachofanya — au umefanya — kulinda amani yako wakati ulimwengu unaokuzunguka unahisi kama mzigo mzito sana?',
 'approved', NOW(), NOW()),

-- ═══════════════════════════════════════════════
-- LOVE
-- ═══════════════════════════════════════════════
('en', 'love', 'quote',
 'The greatest love you will ever receive is the kind that sees the mess of you and does not flinch — that stays, not because it is easy, but because leaving would be unthinkable.',
 'approved', NOW(), NOW()),

('sw', 'love', 'quote',
 'Upendo mkubwa zaidi utakaowahi kupata ni ule unaouona upotovu wako na hautetemeki — unaoona, si kwa sababu ni rahisi, bali kwa sababu kutoka kungelikuwa hafifu.',
 'approved', NOW(), NOW()),

('en', 'love', 'paragraph',
 'People talk about love as if it arrives clean and certain. But the love that actually sustains a life rarely looks like the movies. It looks like someone sitting with you in your lowest moment and saying nothing — just being there. It looks like a parent who never had the words but always had the presence. Like a friend who flew an overnight bus to be at your side during a crisis. Like a stranger who saw you crying in public and handed you water without making it weird. Love is quieter and more ordinary than we have been told. But it is also more powerful.',
 'approved', NOW(), NOW()),

('sw', 'love', 'paragraph',
 'Watu wanazungumza kuhusu upendo kana kwamba unafika safi na kwa uhakika. Lakini upendo unaodumisha maisha mara nyingi hauonekani kama sinema. Unaonekana kama mtu akikaa nawe katika wakati wako wa chini kabisa na kutosema chochote — kuwepo tu. Unaonekana kama mzazi ambaye hakuwa na maneno kamwe lakini alikuwa na uwepo daima. Kama rafiki aliyepanda basi usiku kuwa upande wako wakati wa msiba. Kama mgeni aliyekuona ukilia hadharani na kukupa maji bila kukufanya uone aibu. Upendo ni wa utulivu zaidi na wa kawaida zaidi kuliko tunavyoambiwa. Lakini pia una nguvu zaidi.',
 'approved', NOW(), NOW()),

('en', 'love', 'lesson',
 'I grew up in a home where love was conditional — and it took me decades to recognise how that shaped every relationship I entered as an adult. I kept choosing people who confirmed my beliefs about love: that it had to be earned, that it could be taken away, that I was never quite enough. Therapy helped me rewrite that story. The most profound love lesson I have learned is this: you cannot receive what you do not believe you deserve. The work of healing your relationship with love starts on the inside — not in finding the right person. — HopeSpace Admin',
 'approved', NOW(), NOW()),

('sw', 'love', 'lesson',
 'Nilikua katika nyumba ambapo upendo ulikuwa na masharti — na ilinichukua miongo kugundua jinsi hiyo ilivyoumba kila uhusiano nilioingia kama mtu mzima. Niliendelea kuchagua watu waliokubaliana na imani zangu kuhusu upendo: kwamba ulipaswa kupatikana, kwamba ungeweza kuchukuliwa, kwamba sikutosha kamwe. Matibabu yalisaidia kuandika upya hadithi hiyo. Somo zuri zaidi la upendo nililolijifunza ni hili: huwezi kupokea unachokuamini hustahili. Kazi ya kuponya uhusiano wako na upendo inaanza ndani — si katika kupata mtu sahihi. — HopeSpace Admin',
 'approved', NOW(), NOW()),

('en', 'love', 'question',
 'What does love look like in your life that nobody would see if they just looked at your public image?',
 'approved', NOW(), NOW()),

('sw', 'love', 'question',
 'Upendo unaonekanaje katika maisha yako ambayo hakuna mtu angeona kama angelitazama taswira yako ya umma tu?',
 'approved', NOW(), NOW()),

-- ═══════════════════════════════════════════════
-- FINANCE
-- ═══════════════════════════════════════════════
('en', 'finance', 'quote',
 'Wealth is not about how much you earn. It is about the distance between what you earn and what you spend — and what you do with that distance over time.',
 'approved', NOW(), NOW()),

('sw', 'finance', 'quote',
 'Utajiri si kuhusu kiasi unachopata. Ni kuhusu umbali kati ya unachopata na unachotumia — na unachofanya na umbali huo baada ya muda.',
 'approved', NOW(), NOW()),

('en', 'finance', 'paragraph',
 'Most people in financial trouble are not there because they did not work hard enough. They are there because nobody taught them how money actually works — how compound interest can build wealth when it works for you, or destroy your future when it works against you in debt. Financial education is not a privilege for the rich. It is the most urgent knowledge gap in communities that have been systematically underpaid and underbanked. Understanding money is a form of self-defence.',
 'approved', NOW(), NOW()),

('sw', 'finance', 'paragraph',
 'Watu wengi wenye matatizo ya fedha hawapo pale kwa sababu hawakufanya kazi kwa bidii ya kutosha. Wako pale kwa sababu hakuna aliyewafundisha jinsi pesa inavyofanya kazi kweli kweli — jinsi riba ya mkusanyiko inavyoweza kujenga utajiri inapofanya kazi kwa ajili yako, au kuharibu siku yako ya kesho inapofanya kazi dhidi yako katika madeni. Elimu ya kifedha si haki ya matajiri. Ni pengo muhimu zaidi la maarifa katika jamii ambazo zimepata mishahara ya chini na huduma duni za benki kwa makusudi. Kuelewa pesa ni aina ya kujilinda.',
 'approved', NOW(), NOW()),

('en', 'finance', 'lesson',
 'At thirty years old I had a good salary and nothing to show for it. No savings, no investments, no plan — just a lifestyle that matched my income and left nothing over. The turning point was tracking every single shilling for ninety days. Not to judge myself. Just to see. What I saw horrified me. I was spending 40% of my net income on things I did not even remember buying. That awareness created the space for a different choice. In two years I cleared my debts and built a six-month emergency fund. The math was always there. What I was missing was the mirror. — HopeSpace Admin',
 'approved', NOW(), NOW()),

('sw', 'finance', 'lesson',
 'Nilipokuwa na miaka thelathini nilikuwa na mshahara mzuri na hakuna chochote cha kuonyesha. Hakuna akiba, hakuna uwekezaji, hakuna mpango — tu mtindo wa maisha uliofanana na mapato yangu na kuacha hakuna zaidi. Ncha ya mabadiliko ilikuwa kufuatilia kila shilingi kwa siku tisini. Si kujihukumu. Kuona tu. Niliyoona kilinishtua. Nilikuwa nikitumia 40% ya mapato yangu halisi kwa vitu ambavyo sikumbuki hata kununua. Uangalifu huo uliunda nafasi ya chaguo tofauti. Katika miaka miwili nililipa madeni yangu na kujenga akiba ya dharura ya miezi sita. Hisabati ilikuwa hapo daima. Kilichokuwa kinakosekana ni kioo. — HopeSpace Admin',
 'approved', NOW(), NOW()),

('en', 'finance', 'question',
 'What is the one financial habit — good or bad — that has had the biggest impact on your life? Would you do it differently?',
 'approved', NOW(), NOW()),

('sw', 'finance', 'question',
 'Ni tabia moja ya kifedha — nzuri au mbaya — ambayo imekuwa na athari kubwa zaidi katika maisha yako? Je, ungefanya tofauti?',
 'approved', NOW(), NOW()),

-- ═══════════════════════════════════════════════
-- INVESTMENT TIPS
-- ═══════════════════════════════════════════════
('en', 'investment_tips', 'quote',
 'The best time to start investing was years ago. The second best time is right now — even with what you have, even with what you know.',
 'approved', NOW(), NOW()),

('sw', 'investment_tips', 'quote',
 'Wakati bora wa kuanza kuwekeza ulikuwa miaka iliyopita. Wakati wa pili bora ni sasa hivi — hata na unachokiwa nacho, hata na unachokijua.',
 'approved', NOW(), NOW()),

('en', 'investment_tips', 'paragraph',
 'The most powerful investment tool available to ordinary people in East Africa is not the stock market. It is time. A small amount of money invested consistently over twenty years — even in a SACCOS or a government savings bond — will grow to a sum that feels impossible from where you are standing today. The mathematics of compound growth is ruthlessly patient. It does not care whether you started poor. It cares whether you started. The investor who starts with twenty thousand shillings a month at twenty-five will retire better than the one who starts with a hundred thousand at forty-five.',
 'approved', NOW(), NOW()),

('sw', 'investment_tips', 'paragraph',
 'Chombo chenye nguvu zaidi cha uwekezaji kinachopatikana kwa watu wa kawaida Afrika Mashariki si soko la hisa. Ni wakati. Kiasi kidogo cha pesa kilichowekwa kwa uthabiti kwa miaka ishirini — hata katika SACCOS au hati ya akiba ya serikali — kitakua hadi jumla ambayo inahisi kuwa haiwezekani kutoka mahali unposimama leo. Hisabati ya ukuaji wa mkusanyiko ina subira ya kipekee. Haijali kama ulianza maskini. Inajali kama ulianza. Mwekezaji anayeanza na shilingi elfu ishirini kwa mwezi akiwa na miaka ishirini na mitano atastaafidu vizuri zaidi kuliko yule anayeanza na laki moja akiwa na miaka arobaini na mitano.',
 'approved', NOW(), NOW()),

('en', 'investment_tips', 'lesson',
 'I had a colleague who earned almost exactly what I did. We worked at the same company for eleven years. When we both left, he had built a rental property, two active businesses, and a healthy SACCOS account. I had a nice car and a closet of clothes. The difference was not income — it was intention. Every month he allocated before he spent. I spent before I allocated. His money worked while he slept. Mine just… left. I changed my system completely after that, and within five years the results were visible. Income matters less than what you do with it the moment it arrives. — HopeSpace Admin',
 'approved', NOW(), NOW()),

('sw', 'investment_tips', 'lesson',
 'Nilikuwa na mwenzangu wa kazi ambaye alipata karibu sawa na mimi. Tulifanya kazi kwenye kampuni moja kwa miaka kumi na moja. Tulipomaliza wote wawili, yeye alikuwa amejenga nyumba ya kupangisha, biashara mbili zinazofanya kazi, na akaunti ya SACCOS yenye afya. Nilikuwa na gari zuri na kabati la nguo. Tofauti haikuwa mapato — ilikuwa nia. Kila mwezi yeye alipanga fedha kabla ya kutumia. Mimi nilitumia kabla ya kupanga. Pesa zake zilifanya kazi wakati alilala. Zangu tu... zilienda. Nilibadilisha mfumo wangu kabisa baada ya hilo, na ndani ya miaka mitano matokeo yalikuwa wazi. Mapato yanahusika kidogo kuliko unachofanya nayo wakati yanafika. — HopeSpace Admin',
 'approved', NOW(), NOW()),

('en', 'investment_tips', 'question',
 'If you could start one investment today with whatever amount you have — what would it be, and what is actually stopping you?',
 'approved', NOW(), NOW()),

('sw', 'investment_tips', 'question',
 'Kama ungeweza kuanza uwekezaji mmoja leo na kiasi chochote unacho — ungekuwa nini, na ni nini kinachokuzuia kweli kweli?',
 'approved', NOW(), NOW());

-- ============================================================
-- 3. TESTIMONIES
-- ============================================================
INSERT IGNORE INTO `testimonies` (`alias`, `content`, `language`, `status`, `created_at`) VALUES

('Mwalimu Amina, Dodoma',
 'Nilifundisha kwa miaka kumi na mbili kwa mshahara mdogo, nikifikiria mara kwa mara kwamba maisha yangu yangebadilika siku moja. Lakini siku ile haikuja peke yake. Ilikuja nilipoamua kujibadilisha mimi mwenyewe. Nilianza kuchukua masomo ya ziada ya mtandaoni usiku wa manane, baada ya watoto wangu kulala. Miaka mitatu baadaye, nimepata cheti cha usimamizi wa elimu na leo ninasimamia shule za kata nzima. Hakuna haja ya kusubiri maisha yabadilishe. Wewe ndiye mabadiliko.',
 'sw', 'approved', NOW()),

('Teacher Amina, Dodoma',
 'I taught for twelve years on a small salary, always thinking my life would change someday. But that day never came on its own. It came when I decided to change myself. I started taking online courses late at night after my children went to sleep. Three years later, I have an educational management certificate and now I oversee schools across my entire district. Do not wait for life to change. You are the change.',
 'en', 'approved', NOW()),

('Daktari mdogo, Mwanza',
 'Nilikuwa karibu kuacha masomo ya udaktari mwaka wa tatu. Sababu hazikuhusu akili  nilikuwa na akili ya kutosha. Zilikuhusu nguvu ya moyo. Nilikuwa nikiishi mbali na familia, nikijisikia mwenyewe kabisa, na udaktari ulionekana kama mlima ambao nisingeweza kupanda. Usiku mmoja niliandika barua ya kujiaga. Haikutumwa. Asubuhi yake nilikwenda kwenye kliniki ya ushauri wa chuo na kuanza mazungumzo yaliyobadilisha kila kitu. Ninafanya kazi kama daktari leo. Kama uko mahali nilipokuwa  tafadhali tafuta msaada. Hatua hiyo moja inaweza kubadilisha kila kitu.',
 'sw', 'approved', NOW()),

('Junior Doctor, Mwanza',
 'I nearly quit medical school in third year. Not because of my grades  my grades were fine. It was my spirit that was broken. I was living far from family, deeply isolated, and medicine looked like a mountain I had no strength left to climb. One night I wrote a farewell letter. It was never sent. The next morning I walked to the university counselling clinic. That conversation changed everything. I am a practising doctor today. If you are where I was  please get help. That one step can change everything.',
 'en', 'approved', NOW()),

('Bibi Fatuma, Tanga',
 'Nilibeba msalaba wa mtoto wangu wa pekee kufariki akiwa na miaka ishirini na mane. Haukuwa kifo cha ugonjwa  alijichagua. Miaka mitatu ya kwanza ilikuwa giza totoro. Sikuelewa. Nilikuwa na hasira. Niliomba Mungu kwa machozi ya damu. Lakini baada ya muda mrefu wa msaada wa kikundi cha ushauri, nilijifunza kitu kimoja ambacho kiliniua na kunihuisha: msongo wa mawazo ni ugonjwa, si chaguo. Mtoto wangu alikuwa mgonjwa na hakupona. Sasa ninasimamia kikundi kinachasaidia familia nyingine zinazopita mahali nilipopita. Maumivu yangu yamekuwa dawa ya mtu mwingine.',
 'sw', 'approved', NOW()),

('Mama Fatuma, Tanga',
 'I carried the grief of losing my only child at twenty-four. It was not illness that took him  he chose to leave. The first three years were complete darkness. I did not understand. I was angry. I screamed at God. But after a long journey of grief counselling and community support, I learned one truth that both destroyed and rebuilt me: a mental health crisis is an illness, not a choice. My son was sick and did not recover. I now lead a support group for other families walking the road I walked. My pain became someone else''s medicine.',
 'en', 'approved', NOW()),

('Mkurugenzi wa biashara, Dar es Salaam',
 'Nilificha unyogovu wangu kwa miaka saba nyuma ya suti nzuri na tabasamu la mikutano. Kila asubuhi nilikuwa nikiinua wengine kwa hotuba na matumaini, wakati usiku nikirudi nyumbani nikijiziba mlangoni na kutoweza kufanya lolote. Ilichukua mwenzangu wa zamani wa chuo kunipiga simu usiku mmoja na kusema "Ninajua kitu kimekwenda vibaya  niambie ukweli." Niliomba mabadiliko. Nilichukua wanga wa kupona. Kujifunua kwangu kwa timu yangu kulifikia vizuri kuliko nilivyotegemea. Ukweli unaodhuriku kamwe haulingani na mfumo wa uongo unaokukandamiza. Zungumza.',
 'sw', 'approved', NOW()),

('Business Director, Dar es Salaam',
 'I hid my depression for seven years behind a good suit and a meeting-room smile. Every morning I lifted others with speeches and hope, while at night I returned home and locked the door and could do nothing. It took an old university colleague calling me one night and saying "I know something is wrong  tell me the truth." I told him. I took a leave of recovery. My openness with my team landed better than I ever expected. The truth that shames you is never as heavy as the lie that drowns you. Speak.',
 'en', 'approved', NOW()),

('Mama Zawadi, Iringa',
 'Nilikuwa mama wa watoto wanne bila ya mumewe, nikifanya kazi kama msafi katika ofisi. Nilijaribu kuomba mkopo wa benki mara tatu  nilikataliwa mara tatu kwa sababu ya ukosefu wa dhamana. Jirani yangu aliniambia kuhusu SACCOS yetu ya mtaa. Nilianza na shilingi elfu tano kwa mwezi. Mwaka wa kwanza nilicheka kidogo tu  kiasi kilikuwa kidogo sana. Lakini niliendelea. Mwaka wa tano nilichukua mkopo mkubwa wa kwanza. Mwaka wa saba niliweka akili ya mwanzo. Leo sijui kuhesabu nguvu milele  ninajua tu kwamba shilingi elfu tano moja zilibadilisha maisha yangu na ya watoto wangu wanne.',
 'sw', 'approved', NOW()),

('Mama Zawadi, Iringa',
 'I was a single mother of four, working as a cleaner in an office building. I tried to get a bank loan three times and was rejected three times for lack of collateral. A neighbour told me about our local SACCOS. I started with five thousand shillings a month. The first year I laughed a little at myself  the amount felt so small. But I kept going. By year five I took my first major loan. By year seven I owned my first asset. Today I cannot calculate the total power of it  I only know that five thousand shillings changed my life and the lives of my four children.',
 'en', 'approved', NOW());

-- ============================================================
-- 4. STORIES  Original, Compelling Adult Fiction & Narrative
--    Author: HopeSpace Admin
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET @admin_id = (SELECT `id` FROM `users` WHERE `username` = 'hopespace_admin' LIMIT 1);

-- 
-- Story 1 (EN): The Last Call  A story about a man who saves a life
-- 
INSERT IGNORE INTO `stories` (
    `author_id`, `title`, `slug`, `language`, `description`,
    `story_type`, `status`, `is_complete`, `created_at`, `updated_at`
) VALUES (
    @admin_id,
    'The Last Call',
    'the-last-call',
    'en',
    'On a Tuesday night in Dar es Salaam, a tired taxi driver receives a passenger who changes the course of his entire life  and whose life he unknowingly saves. A story about what can happen in an ordinary hour when one person chooses to be present.',
    'full', 'approved', 1, NOW(), NOW()
);

SET @s1 = (SELECT `id` FROM `stories` WHERE `slug` = 'the-last-call' LIMIT 1);

INSERT IGNORE INTO `story_parts` (`story_id`, `part_number`, `content`, `status`, `is_last_part`, `created_at`) VALUES (
    @s1, 1,
    '<p>Daniel had been driving for eleven hours when he accepted the last fare of the night.</p>

<p>He almost did not. His back hurt, his phone battery was at seven percent, and the navigation kept rerouting him through a construction stretch near Kariakoo that added twelve minutes to every trip. He was done. He was going home.</p>

<p>Then the notification came in  a pickup on Msasani Peninsula, close enough to the road home that it seemed foolish to refuse. He accepted it and pulled over to adjust his mirror, which had been crooked since a near-miss three days ago that he had not yet had the energy to fix.</p>

<p>The passenger was a woman in her late thirties. She wore a grey blazer, carried a leather bag too heavy for one shoulder, and said nothing when she got in. She gave him an address on the other side of the city  an area of old apartment buildings near the railway line  and then sat looking out the window at the harbour lights.</p>

<p>Daniel drove. He was good at reading passengers. Some wanted silence. Some wanted the radio. A few wanted to talk, and those were usually the ones who had no one waiting at home. He left the radio off and let the city pass them in amber streaks.</p>

<p>They were twenty minutes into the trip when the woman said, without turning from the window: "Do you ever feel like you have been running so long that when you finally stop, there is nothing left?"</p>

<p>Daniel did not answer immediately. He was not sure if she was talking to him or to the glass.</p>

<p>"Every week," he said eventually. "Every single week by Thursday."</p>

<p>She turned then and looked at him properly for the first time. "And what do you do about it?"</p>

<p>He thought for a moment. He thought about his daughter, who was six and left notes for him under the door when he came home too late to knock. He thought about his mother, who made pilau every Friday because it was the only meal she knew he would not eat on the road. He thought about how those things  small, ordinary, unremarkable things  were the reason he drove back every night instead of somewhere else entirely.</p>

<p>"I think about what is waiting for me," he said.</p>

<p>The woman was quiet for a moment. Then she said: "What if nothing is waiting?"</p>

<p>And Daniel understood, then, what kind of trip this was. He did not panic. He did not preach. He simply changed course  literally, gently  and drove toward a stretch of seafront road where the lights were widest and the city breathed a little easier.</p>

<p>"Let me show you something," he said. "Just two minutes."</p>

<p>He pulled over near a small open space above the water where night fishermen sometimes gathered with their lanterns. There were three of them out there that night, quiet and patient in the dark, their lines in the black water.</p>

<p>Daniel turned off the engine.</p>

<p>"I used to stop here when I thought there was no point," he said. "Not to fish. Just to watch. There is something about a man who sits in the dark and waits  believing the water will give him something  that I could not stop thinking about when I was struggling."</p>

<p>The woman looked at the fishermen for a long time.</p>

<p>"Faith," she said quietly. Not a question. More like a word she had almost forgotten.</p>

<p>"Or stubbornness," Daniel said. "I am still not sure which one keeps us going. Maybe both."</p>

<p>She laughed. It was small and tired, but it was real.</p>

<p>They sat there for fifteen minutes. He did not charge her for the wait. When they finally drove on, she asked him his name, and when he dropped her at the address she had given him she handed him the fare plus enough extra to cover most of what he had spent on fuel that day.</p>

<p>"Thank you," she said. "I needed to be reminded that someone is still casting a line."</p>

<p>He never saw her again. But he added the layover at the seafront to his late-night route, and he kept the radio off on his last fare of every shift  in case anyone else needed eleven minutes and someone who had learned how to simply be present.</p>

<p><em>Written by HopeSpace Admin.</em></p>',
    'approved', 1, NOW()
);

-- 
-- Story 2 (SW): Mwisho wa Barabara  Hadithi ya kurudi nyumbani
-- 
INSERT IGNORE INTO `stories` (
    `author_id`, `title`, `slug`, `language`, `description`,
    `story_type`, `status`, `is_complete`, `created_at`, `updated_at`
) VALUES (
    @admin_id,
    'Mwisho wa Barabara',
    'mwisho-wa-barabara',
    'sw',
    'Baada ya miaka kumi na mbili nje ya nchi, Grace anarudi Tanzania akiwa amechoka, amefilisika na akijisikia kama mgeni katika nchi yake mwenyewe. Lakini siku ya kwanza nyumbani inamfundisha kitu ambacho safari yake yote haikuweza kumfundisha.',
    'full', 'approved', 1, NOW(), NOW()
);

SET @s2 = (SELECT `id` FROM `stories` WHERE `slug` = 'mwisho-wa-barabara' LIMIT 1);

INSERT IGNORE INTO `story_parts` (`story_id`, `part_number`, `content`, `status`, `is_last_part`, `created_at`) VALUES (
    @s2, 1,
    '<p>Ndege ilishuka Dar es Salaam saa mbili usiku, na Grace alishuka akibeba begi moja la mwili  kitu pekee kilichobaki kutoka kwa maisha aliyojenga London kwa miaka kumi na miwili.</p>

<p>Hakuomba mtu amchukue. Alichukua daladala mpaka Ubungo, kisha nyingine mpaka Temeke, akiwa kimya katika giza na harufu ya jiji alilolikimbia akiwa na miaka ishirini na mbili.</p>

<p>Mama yake alimlinda mlangoni. Hakusema neno. Alimkumbatia tu  kwa muda mrefu, bila kuuliza maswali, bila kutaka maelezo. Kumbatia ile ilikuwa lugha ambayo London haikuwahi kumfundisha Grace  lugha isiyohitaji mafanikio kama tiketi ya kuingia.</p>

<p>Usiku wa kwanza, Grace alikaa nje kwenye baraza la nyuma hadi alfajiri. Alila mabuyu ya mama yake na akafikiria maisha yake yote.</p>

<p>Alikuwa amekwenda London akiwa bingwa. Akarudi akiwa mvunjika. Biashara aliyoijenga kwa miaka saba ilianguka katika kipindi cha miezi sita ya mshtuko wa uchumi na maamuzi mabaya aliyoyafanya haraka haraka. Uhusiano wake uliisha Machi. Nyumba yake ya kukodisha iliisha Aprili. Ndege yake iliondoka Mei.</p>

<p>Katika baraza lile usiku, Grace aliuliza swali alilokuwa amekimbia kwa miaka yote: Je, nilifanikiwa? Na jibu lililokuja kwa utulivu wa kutisha lilikuwa: inategemea unamaanisha nini.</p>

<p>Asubuhi ilipowadia, jirani yao wa zamani  mzee Rashidi, aliyefundisha sayansi shuleni kwa miaka thelathini  alipita ukiwa na birika la chai ya tangawizi. Hakujua Grace amerudi. Alipomwona alikaa pembeni yake bila kusema lolote, akamiminika chai, akampe kikombe.</p>

<p>"Unaonekana umechoka," alisema mwishowe.</p>

<p>"Nimechoka sana," Grace alisema. Ukweli aliouandika mara ya kwanza kwa maneno.</p>

<p>"Chai inasaidia kidogo," Mzee Rashidi alisema. "Na ardhi inasaidia zaidi. Ukiweka miguu yako kwenye ardhi ya nyumbani, mwili unajua uko salama. Ubongo huchukua muda zaidi kujua  lakini mwili unajua mara moja."</p>

<p>Grace alitazama miguu yake. Alikuwa ameweka sandali zake. Akaziuliza mbali na akagusa ardhi ya udongo nyekundu wa baraza kwa nyayo zake uchi  na kwa sababu ya ajabu ambayo haikuelezeka kabisa, machozi yalitiririka bila tahadhari yoyote.</p>

<p>Alilia kwa muda mrefu. Mzee Rashidi alikaa kimya, akinywa chai yake, na kisha aliuliza: "Unataka kuanza upya au kuanza kitu kipya?"</p>

<p>"Sijui tofauti," Grace alisema.</p>

<p>"Nzuri," alisema. "Hiyo inamaanisha bado una uwazi. Watu ambao wanajua jibu walikwisha amua. Wewe bado una nafasi ya kuchagua."</p>

<p>Katika wiki zilizofuata, Grace alianza kufanya kazi katika duka dogo la mama yake, akiandika hesabu na kuweka kumbukumbu ambazo familia haikuwa nazo kwa miaka mingi. Haikuwa ni kazi ya biashara ya kimataifa. Ilikuwa kazi ndogo ya utulivu  na ilikuwa nzuri zaidi kuliko alivyotegemea.</p>

<p>Baada ya miezi minne, jirani mwingine alimwomba msaada wa kuiweka biashara yake ya mboga mtandaoni. Grace alifanya. Kisha mwingine. Kisha alikuwa na wateja watano, kisha kumi.</p>

<p>Hakujipanga kuanzisha kitu. Alijipanga tu kuwa mtu wa manufaa pale alipokuwa. Na kwa sababu hiyo tu, mambo yalianza kujengeka.</p>

<p>Miaka miwili baadaye, Grace alikuwa na kampuni ndogo ya mshauri wa kidijitali, akisaidia biashara ndogo za Dar es Salaam kwenda mtandaoni. Haikuwa mpango alioandika London. Ilikuwa hadithi tofauti kabisa  moja iliyoandikwa kwa kurudi nyumbani, kwa uvumilivu, na kwa mkono wa mzee aliyemimina chai bila kuuliza.</p>

<p>Mara kwa mara, alikuwa anarudi kwa baraza lile usiku wa manane, akinywa kikombe kimoja peke yake, na kukumbuka lile swali: Je, nifanikiwa?</p>

<p>Sasa jibu lilikuwa rahisi zaidi.</p>

<p>Ndio. Lakini si jinsi nilivyofikiri ilivyomaanisha.</p>

<p><em>Imeandikwa na HopeSpace Admin.</em></p>',
    'approved', 1, NOW()
);

-- 
-- Story 3 (EN): The Weight of a Secret  A marriage story
-- 
INSERT IGNORE INTO `stories` (
    `author_id`, `title`, `slug`, `language`, `description`,
    `story_type`, `status`, `is_complete`, `created_at`, `updated_at`
) VALUES (
    @admin_id,
    'The Weight of a Secret',
    'the-weight-of-a-secret',
    'en',
    'James and Miriam have been married for nine years and look, from the outside, like they have everything. But James is carrying a secret that has quietly corroded their marriage for three of those years  and the night he finally breaks, everything shifts. A raw story about truth, trust, and what love looks like when it survives honesty.',
    'full', 'approved', 1, NOW(), NOW()
);

SET @s3 = (SELECT `id` FROM `stories` WHERE `slug` = 'the-weight-of-a-secret' LIMIT 1);

INSERT IGNORE INTO `story_parts` (`story_id`, `part_number`, `content`, `status`, `is_last_part`, `created_at`) VALUES (
    @s3, 1,
    '<p>James made excellent coffee. It was the first thing Miriam had noticed about him  not his height, not his laugh, but the way he attended to the kettle like it mattered. Precision. Care. The belief that small things done well were a form of love.</p>

<p>Nine years later, he made her coffee every morning still. Put it on her side of the kitchen island, milk already stirred, exactly the temperature she liked.</p>

<p>It was on one of those mornings  a Wednesday, unremarkable  that Miriam noticed the mug was already cold when she picked it up.</p>

<p>He had made it and walked away. Which meant something was wrong with him, not the coffee.</p>

<p>She had known for a while that something was sitting between them. Not the kind of thing you could name at the dinner table. More like a slight but consistent pressure  the way two people who love each other can share a house and share a bed and still feel like they are separated by glass they can both see through but neither can break.</p>

<p>That night she found him at the kitchen table at midnight, staring at a laptop screen that had gone dark.</p>

<p>"James."</p>

<p>He did not look up immediately. When he did, she saw  for the first time in nine years  that his eyes were full.</p>

<p>"Sit down," he said.</p>

<p>She sat.</p>

<p>He told her. It came out slowly at first, then all at once  the way things do when they have been held in too long. Three years ago he had made a financial decision he was ashamed of. A scheme he knew was wrong, entered into out of desperation, that had cost them the savings they were going to use for the land they had talked about, the house Miriam had drawn sketches of on the backs of envelopes on long drives.</p>

<p>The money was gone. Had been gone for almost two years. He had been quietly rebuilding, month by month, hoping to replace it before she ever knew. Hoping the lie of omission would expire before it could be told.</p>

<p>Miriam sat still for a very long time.</p>

<p>She thought about anger. She had access to it  it was right there. Three years. Two years of her not knowing. The house sketches. She had kept sketching, all this time, thinking it was still possible in the way she had imagined.</p>

<p>But she also looked at his face. At the man who had made her coffee every morning for nine years even when everything else was falling apart inside him. Who had not run. Who had sat here, at midnight, and finally stopped trying to hold it together alone.</p>

<p>"Why tonight?" she asked.</p>

<p>"Because I can not do it anymore," he said. "The weight of it. I would rather lose everything with the truth than keep everything on a foundation that is a lie."</p>

<p>She reached across the table. Not to forgive  that would take time, and she knew it, and she was not going to pretend otherwise. But to say: I am still here. And we are going to have to walk through this, and it will not be comfortable, and I do not know exactly what the other side looks like.</p>

<p>But I am still at the table.</p>

<p>It took them eighteen months to rebuild  the finances, the trust, the conversation they had avoided for far too long. It was not a smooth process. There were nights when Miriam left the room rather than say something she would regret. There were mornings when James woke up certain she had decided, in the night, that she was done.</p>

<p>But every morning there was still coffee on the island. Made precisely. Still warm.</p>

<p>And by some grace neither of them could fully explain, that was enough to keep going  one cup, one day, one difficult conversation at a time.</p>

<p><em>Written by HopeSpace Admin.</em></p>',
    'approved', 1, NOW()
);

-- 
-- Story 4 (SW): Mwanga wa Taa moja  Hadithi ya msichana aliyeamua
-- 
INSERT IGNORE INTO `stories` (
    `author_id`, `title`, `slug`, `language`, `description`,
    `story_type`, `status`, `is_complete`, `created_at`, `updated_at`
) VALUES (
    @admin_id,
    'Mwanga wa Taa Moja',
    'mwanga-wa-taa-moja',
    'sw',
    'Neema ana miaka ishirini na saba. Ana shahada, ana dhamira. Lakini hakuna kazi, baba yake mgonjwa, na familia inategemea yeye peke yake. Usiku anapoamua kukata tamaa, msichana mdogo jirani yake anamfundisha somo ambalo amekuwa akisahau.',
    'full', 'approved', 1, NOW(), NOW()
);

SET @s4 = (SELECT `id` FROM `stories` WHERE `slug` = 'mwanga-wa-taa-moja' LIMIT 1);

INSERT IGNORE INTO `story_parts` (`story_id`, `part_number`, `content`, `status`, `is_last_part`, `created_at`) VALUES (
    @s4, 1,
    '<p>Neema alikataa ombi la kazi la tatu ndani ya wiki mbili, na alifunga kompyuta saa nne usiku akijua kwamba nguvu yake ilikuwa imeisha.</p>

<p>Hakuwa ameula chakula cha jioni. Alikuwa amesahau. Kati ya kuhudhuria mahojiano mawili ambayo hayakufanikiwa, kubeba baba yake hospitali kwa dawa mpya, na kujaribu kuandika barua ya maombi ya kazi ya tano ambayo hakuwa tayari  chakula hakikuonekana muhimu.</p>

<p>Familia yake iliishi nyumba ya pango mtaa wa Kinondoni  chumba kimoja, jiko moja, meza moja ambayo ilitumikia kama mahali pa kula chakula, pa kusomea, na pa kufanya kazi. Baba yake alilala nyuma ya pazia. Mama yake alikunja nguo zingine chumba jirani.</p>

<p>Neema alikaa nje ya mlango kwenye ngazi ndogo, akitazama mitaani. Taa moja ya umeme ilitoa mwanga wa njano hafifu juu ya barabara ya udongo. Mbu waliimba karibu na masikio yake. Mbali, mbwa aliimbia giza.</p>

<p>Alifikiria mambo mengi usiku ule. Alifikiria shahada yake  miaka minne ya kufanya kazi bila kulala, kula ugali wa bei nafuu, kuomba ufadhili ambao haukutosheleza kamwe  na jinsi ilivyoonekana sasa kama karatasi tu katika folda ambayo hakuna aliyeitaka kuona.</p>

<p>Alifikiria baba yake. Miaka tano ya ugonjwa. Mtu ambaye alikuwa mwalimu wa hesabu  mtu aliyeamini kwamba akili inaweza kuvunja hali yoyote  sasa akitegemeana na mtu anayeomba kazi bila kupata.</p>

<p>Alifikiria kuacha. Si kwa maana ya kutoweka tu  kwa maana ya kukubali kwamba baadhi ya vita hazipatikani.</p>

<p>Kisha alisikia mguu mdogo ukishuka ngazi.</p>

<p>Ilikuwa ni Sadia  mtoto wa jirani, msichana wa miaka saba, aliyetoka nje kwa sabuni. Aliposimama karibu na Neema, alimwangalia kwa makini ya mtoto anayejua kitu kibaya kimetokea lakini hajui nini.</p>

<p>"Unalilia?" Sadia alimwuliza.</p>

<p>"Hapana," Neema alisema. Kisha: "Kidogo."</p>

<p>Sadia aliketi karibu naye  bila ruhusa, bila kujali kwamba ngazi ilikuwa ndogo sana kwa watu wawili. Akakaa pale tu.</p>

<p>"Bibi yangu anasema ukilia usiku maana yake moyo wako unafanya kazi," Sadia alisema. "Moyo usioumia ni moyo ambao haujali kitu chochote."</p>

<p>Neema alimtazama. "Bibi yako ni mzuri."</p>

<p>"Yeye ni mzee tu," Sadia alisema kwa utulivu. Kisha akanyanyua kichwa chake kuelekea taa ile ya njano iliyoangaza barabara. "Unajua kwa nini taa ile iko pale?"</p>

<p>Neema alitazama. "Ili tuone barabara."</p>

<p>"Mimi nafikiri," Sadia alisema kwa uzito wa mtoto aliyefikiri sana, "ipo ili wadudu waelekee kitu. Kama haingekuwepo, wadudu wote wangeacha bila kujua waende wapi. Hata mbu wetu wa jioni wangehitaji lengo."</p>

<p>Neema alimtazama kwa muda  na kisha kicheko kidogo, cha kweli, kilimtoka. Si kicheko cha furaha. Kicheko cha mtu ambaye amechoka sana hadi upole mdogo unauma.</p>

<p>"Kweli unazungumza nini, Sadia?"</p>

<p>"Ninakuambia," Sadia alisema kwa utulivu kabisa, "kwamba hata usiku wa giza, taa moja inatosha kwa wadudu. Kwa nini isitoshe kwako wewe?"</p>

<p>Walibaki pale kimya kwa muda. Sadia alirudi ndani baada ya mara. Neema alibaki nje mpaka mbu walipochoka naye.</p>

<p>Asubuhi iliyofuata aliamka saa tisa, akawasha kompyuta  na akamalizia barua ile ya maombi ya kazi ya tano. Haikuwa nzuri. Alikuwa amechoka sana kuifanya kuwa kamili. Lakini aliituma.</p>

<p>Kazi ile ya tano ndiyo ilimjibu baada ya siku tatu.</p>

<p>Miaka miwili baadaye, Neema alisimulia hadithi hii katika kikao cha msaada wa wasichana wa chuo kikuu, akisema: usidharau mwanga mdogo. Ukiwa katika giza totoro, hata kitu kidogo kinachong'aa kinatosha kukuonyesha njia ya hatua moja tu mbele. Na hatua moja ndio unachohitaji.</p>

<p><em>Imeandikwa na HopeSpace Admin.</em></p>',
    'approved', 1, NOW()
);


-- ─────────────────────────────────────────────
-- Story 5 (EN): Everything After Tuesday — A romantic story
-- ─────────────────────────────────────────────
INSERT IGNORE INTO `stories` (
    `author_id`, `title`, `slug`, `language`, `description`,
    `story_type`, `status`, `is_complete`, `created_at`, `updated_at`
) VALUES (
    @admin_id,
    'Everything After Tuesday',
    'everything-after-tuesday',
    'en',
    'Amara and David met at a coffee shop during a power cut, argued over one candle, and went their separate ways — convinced they would never see each other again. Three years later, life brings them back to the same table. A slow, warm story about timing, second chances, and the kind of love that waits.',
    'full', 'approved', 1, NOW(), NOW()
);

SET @s5 = (SELECT `id` FROM `stories` WHERE `slug` = 'everything-after-tuesday' LIMIT 1);

INSERT IGNORE INTO `story_parts` (`story_id`, `part_number`, `content`, `status`, `is_last_part`, `created_at`) VALUES (
    @s5, 1,
    '<p>The power went out at exactly the moment Amara sat down with her laptop and a plan to finish three overdue reports.</p>

<p>The coffee shop — a narrow place on a side street in Posta that she came to specifically because nobody she knew came here — went dark and then warm orange as the owner lit candles and set them on the tables with the resigned efficiency of someone who had done this many times before.</p>

<p>The problem was that there was only one candle left, and the man who had just walked in — tall, slightly damp from the drizzle outside, already scanning the room with the focused energy of someone who also intended to work — sat down at the table directly across from her and reached for it.</p>

<p>"I was going to take that," Amara said.</p>

<p>He looked at her. He had the kind of face that looked like it was deciding something.</p>

<p>"I got here after you," he said. "But I have a meeting in forty minutes and I need to read something."</p>

<p>"I have three reports due tonight and I am already behind."</p>

<p>He considered this. Then he pulled the candle to the centre of the table, equidistant between them, and sat down across from her.</p>

<p>"Shared resource," he said. "David."</p>

<p>"Amara," she said, pulling her laptop toward the light. "You are blocking my signal by an inch."</p>

<p>"You have my left elbow. We are even."</p>

<p>They worked in what was, after the initial friction, a surprisingly comfortable silence. The candle threw a small circular light between them. Outside the rain got worse. The coffee shop filled with the particular hum of people waiting out weather together — conversations lowered, cups refilled, nobody in a hurry to go anywhere.</p>

<p>At some point David looked up and said: "How do you cite a policy document that has no official author?"</p>

<p>Amara looked up. "What format?"</p>

<p>"APA."</p>

<p>"Use the organisation name as the author. Year in brackets. Then the title in italics."</p>

<p>He looked at her with a new expression — not gratitude exactly, more like recalibration. "You know APA citation style."</p>

<p>"I have three postgraduate reports due tonight," she said. "I live in APA citation style."</p>

<p>"Right." He went back to his document. Then: "Thank you."</p>

<p>The power came back on forty minutes later. They both looked up at the same time, blinking in the sudden fluorescent glare, and the moment — whatever had been building in it quietly — was broken.</p>

<p>He closed his laptop. Checked his watch. Stood.</p>

<p>"Good luck with the reports," he said.</p>

<p>"Good luck with the meeting," she said.</p>

<p>He walked out. She watched him go — not for long, just the way you watch someone leave a space they briefly occupied — and then she went back to her reports. She did not think about him again that night. She was too busy.</p>

<p>She thought about him the next morning, though. And the morning after. Just once or twice, the way you think about a book you only read the first page of and then set down because the timing was wrong.</p>

<hr>

<p>Three years later, she walked into a conference room at a firm she had just been seconded to and found him sitting at the end of the table with a lanyard that identified him as the lead consultant on the project she had been assigned to.</p>

<p>He saw her at the same moment she saw him. Something crossed his face — recognition, surprise, and then something that landed quietly afterward, like a door opening rather than closing.</p>

<p>"Coffee shop," he said. "Posta. Power cut. APA."</p>

<p>"The candle," she said.</p>

<p>"I still feel guilty about the candle."</p>

<p>"You should. It was clearly closer to my side."</p>

<p>He smiled. It was a good smile — the kind that arrived slowly and stayed.</p>

<p>"Can I buy you a coffee?" he said. "To compensate. Belatedly."</p>

<p>"We have a briefing in four minutes," she said.</p>

<p>"After the briefing, then."</p>

<p>She sat down across from him — equidistant again from the centre of the table — and opened her folder.</p>

<p>"After the briefing," she said.</p>

<p>The coffee lasted two hours. The project lasted six months. And when it ended, neither of them suggested going back to their separate lives — because by then, something had quietly and thoroughly rearranged itself, the way a room looks different after someone has lived in it for a while and you realise it was better than it was before.</p>

<p>On a Tuesday evening, eighteen months later, David set a candle on the dining table — a real one, not a power-cut emergency — and asked her a question that had been building since a rainy night in a coffee shop on a side street she had chosen precisely because nobody she knew ever went there.</p>

<p>She said yes.</p>

<p>She said she had known she would, more or less, since the moment he pushed the candle to the centre and said: shared resource.</p>

<p><em>Written by HopeSpace Admin.</em></p>',
    'approved', 1, NOW()
);

-- ─────────────────────────────────────────────
-- Story 6 (SW): Baada ya Mvua — Hadithi ya upendo wa pili
-- ─────────────────────────────────────────────
INSERT IGNORE INTO `stories` (
    `author_id`, `title`, `slug`, `language`, `description`,
    `story_type`, `status`, `is_complete`, `created_at`, `updated_at`
) VALUES (
    @admin_id,
    'Baada ya Mvua',
    'baada-ya-mvua',
    'sw',
    'Sudi aliamua kamwe asipende tena baada ya moyo wake kuvunjwa kwa mara ya pili. Kisha akakutana na Leilah — mwanamuziki asiye na nyumba ya kudumu, mwenye kicheko kikubwa na maswali makubwa zaidi. Hadithi ya watu wawili waliojeruhiwa wanaogundua kwamba upendo wa kweli haucheki vidonda — unakaa pamoja nazo.',
    'full', 'approved', 1, NOW(), NOW()
);

SET @s6 = (SELECT `id` FROM `stories` WHERE `slug` = 'baada-ya-mvua' LIMIT 1);

INSERT IGNORE INTO `story_parts` (`story_id`, `part_number`, `content`, `status`, `is_last_part`, `created_at`) VALUES (
    @s6, 1,
    '<p>Sudi aliamua kuwa na miaka thelathini na minne, baada ya uhusiano wa pili ambao uliishia kwa njia ambayo hakudhani ingewezekana, kwamba upendo ulikuwa mchezo ambao wengine waliucheza vizuri na yeye hakuwa mmoja wao.</p>

<p>Haikuwa uamuzi wa hasira. Ulikuwa uamuzi wa utulivu — ule wa mtu ambaye amejaribu kwa dhati mara mbili na kujifunza kitu: maumivu ya kupoteza mtu anayependwa yanachukua muda mrefu zaidi ya mtu yeyote atakayekuambia.</p>

<p>Kwa hiyo aliishi vizuri. Alifanya kazi yake ya ubunifu wa picha, alikuwa na marafiki wazuri, alikuwa na mama yake aliyepiga simu kila Jumamosi na kuuliza swali moja ambalo hakujibu — na maisha yaliendelea, laini na ya amani, bila ya mahali pa kupotea ndani yake.</p>

<p>Alikutana na Leilah katika usiku wa tamasha dogo la muziki lilalofanyika kwenye paa la jengo la Mchafukoge — jioni ya Ijumaa ambayo alikuwa amekuja tu kwa sababu rafiki yake Kwame alimhonga kwa ahadi ya nyama choma.</p>

<p>Leilah alicheza gitaa na akaimba — sauti yake haikuwa kamili, lakini ilikuwa ya kweli kwa njia ambayo ilimfanya Sudi asimame na kuacha kula. Alikuwa na nywele zilizofungwa vibaya, viatu vya rangi mbili tofauti kwa makusudi, na akaongea na hadhira kati ya nyimbo kwa utulivu wa mtu asiyejali kuonekana vizuri — ambayo, Sudi alifikiri, ilikuwa aina ya ujasiri ambao haukuweza kunukuliwa.</p>

<p>Hawakuzungumza usiku ule. Sudi alimwangalia tu — kwa kujizuia, kwa starehe, kwa njia ya mtu anayetazama kitu kizuri bila nia ya kukimiliki.</p>

<p>Kwame, bila shaka, alimwona.</p>

<p>"Nenda umsalimie," Kwame alisema.</p>

<p>"Hapana," Sudi alisema.</p>

<p>"Kwa nini?"</p>

<p>"Kwa sababu siwezi."</p>

<p>"Ni sababu."</p>

<p>"Ni sababu yangu."</p>

<p>Kwame alienda kumwambia Leilah mwenyewe — bila ruhusa, bila aibu — kwamba rafiki yake alipenda kazi yake na alikuwa na aibu kuja kusema hivyo mwenyewe. Leilah alicheka — sauti ileile ya kweli ambayo ilimfanya Sudi asimame kwanza — na akaja yeye mwenyewe.</p>

<p>"Rafiki yako anasema unapenda muziki wangu," alisema.</p>

<p>"Rafiki yangu hana mipaka," Sudi alisema.</p>

<p>"Ni jibu zuri," Leilah alisema, akiketi pembeni yake bila kuulizwa. "Niambie kitu kimoja ambacho unakipenda kweli kweli — si muziki wangu. Kitu chochote."</p>

<p>Sudi alifikiri. "Mvua," alisema. "Mvua ya kwanza ya msimu. Harufu yake kabla hata ya kushuka."</p>

<p>Leilah akamtazama kwa muda mrefu kidogo. "Hii inaitwa petrichor," alisema. "Harufu ya mvua kabla ya mvua. Watu wengi wanaiishi miaka yote bila kujua ina jina."</p>

<p>"Najua," Sudi alisema. "Nimeitafuta."</p>

<p>"Mtu anayetafuta jina la harufu ya mvua," Leilah alisema polepole, kana kwamba anasoma maneno ya wimbo, "ni mtu wa aina fulani."</p>

<p>"Aina gani?"</p>

<p>"Aina ambayo inabeba mambo kwa njia nzuri — hata mambo ambayo hayaonekani."</p>

<p>Hawakuongea mengi baada ya hapo usiku ule. Lakini kabla ya Sudi kuondoka, Leilah alimwandikia nambari yake kwenye kiganja chake kwa kalamu ya hudhurungi — si kwenye simu, kwenye mkono — na alisema: "Nikiwa mji huu wiki ijayo, tufanye chai."</p>

<p>Wiki iliyofuata akawa mji. Walifanya chai. Saa mbili ikawa nne, nne ikawa usiku, usiku ukawa mazungumzo ya aina ambayo Sudi alikuwa amesahau ilikuwepo — mazungumzo ambayo hayaandaliwi, yanayokwenda mahali wenyewe bila ramani.</p>

<p>Leilah alimwambia kwamba yeye pia alikuwa amepitia upendo mgumu. Kwamba yeye pia alikuwa ameamua mambo ambayo maisha yalimwonyesha yasiyo ya lazima. Kwamba hakukaa mahali pamoja kwa muda mrefu si kwa sababu ya hofu ya karibu — bali kwa sababu hakujapata mahali ambapo alijisikia huru kuwa mzima na mdogo wakati mmoja.</p>

<p>Sudi alimsikiliza. Na kwa mara ya kwanza katika miaka mingi, alihisi kitu — si nzito, si haraka, lakini kitu laini kama pazia linaloinama kidogo kwenye upepo.</p>

<p>Upendo, aligundua baadaye, haucheki vidonda. Haukimbii historia. Unakaa pembeni yake — polepole, kwa heshima — na unasubiri ukaribuni.</p>

<p>Miezi sita baadaye, Leilah alipata kazi ya kudumu Dar es Salaam. Alichukua chumba ndogo karibu na bahari. Na usiku wa kwanza alipowashwa umeme baada ya kuhamia, Sudi alikuwa amekaa kwenye baraza lake na vijiko viwili vya chai, akimwambia kwa maana ambayo maneno haikutosha:</p>

<p>Karibu nyumbani.</p>

<p><em>Imeandikwa na HopeSpace Admin.</em></p>',
    'approved', 1, NOW()
);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Seed complete.
-- Added: 1 author, 72 messages (9 categories x 4 formats x 2 languages),
--        10 testimonies (5 bilingual pairs), 6 stories with full parts
--        (including 2 romantic stories: EN + SW).
-- All content approved and publicly visible immediately.
-- ============================================================
