# Remote audit for Windows PC

*Czech only*

Jednoúčelová naprasená aplikace, kterou jsem před několika lety vyřešil okamžitou potřebu získat systémové reporty z nemanagovaných stanic >300 zaměstnanců rozesetých po celém světě.

Cíle:
- Uživatelsky srozumitelný a blbuvzdorný postup,
- Omezení uživatelů na zaměstatnce,
- Bezpečný přenos dat,
- Automatický sběr dat,
- Napsaná v PHP (!!!), protože v té době jsem jiný jazyk neuměl :)

Celá myšlenka byla poměrně slibně postavena na nepříliš známé aplikaci [msinfo32](https://support.microsoft.com/en-us/help/300887/how-to-use-system-information-msinfo32-command-line-tool-switches), kterou v sobě už léta skrývají všechny distrubuce Windows. Tato poskytuje prakticky kompletní přehled o hardware a konfiguraci systému. Kromě toho umožňuje data exportovat do datového souboru. Plán nakonec zhatil Microsoft, který ve Windows lokalizoval nejen uživatelské rozhraní, ale i exportované datové soubory tak důkladně, že v nich nezbylo nic, z čeho by bylo možné provést jejich hromadné zpracování (např. klíče hodnot byly v češtině s diakritikou, hodnoty pak formátované podle národních zvyklostí), uf!

Zveřejněný kód aplikace je anonymizovaný, ale není těžké odvodit původní vazby – snad uklidním případné obavy prohlášením, že původní endpointy jsou vypnuté, data smazána, klíče revokované a certifikáty expirované.

## Struktura

Nástroj je rozdělen na 3 části:

1. [Webové rozhraní](./www/), zajišťující autentizaci a sběr dat,
2. [Sondu, neboli Windows aplikaci (exe)](./sysinfo/), kterou zaměstnanec spustil na své stanici a ta vytěžila a odeslala reporty,
3. Splácané [parsery](./parser/) pro čtení XML a .nfo souborů.

### Webové rozhraní
Jednoduchá aplikace v Nette, která proti firemnímu G Suite [ověří uživatele](./www/app/model/UserManager.php#L29), [provede uživatele procesem](./www/app/templates/Homepage/default.latte) a [sbírá data](./www/app/presenters/Api.php#L64) od sond.

### Sonda
[Scriptík v PHP](./sysinfo/audit/audit.php), který se spouští na počítači uživatele v CLI režimu. Na Windows samozřejmě PHP engine není, ale to lze snadno vyřešit [přibalením nezbytných binárek](./sysinfo/php/) do distribučního balíčku.

Distribuční balíček není nic jiného, než jeden `.exe` jako obyčejný self-extractor nějakého komprimačního nástroje; v tomto WinRAR. Zvolil jsem [WinRAR self-extractor](https://stackoverflow.com/questions/9265639/how-do-i-make-a-self-installing-executable-using-winrar), protože umožňuje:
- po vybalení dat spustit příkaz (`./php/php.exe ./audit/audit.php`),
- vybalit data dočasného adresáře a po ukončení aplikace je smazat,
- spustit aplikaci s oprávněním administrátora (vyžaduje to `msinfo32`)
- distribuční balíček opatřit ikonou a vlastním popisem,
- konfiguraci self-extractoru uložit pro přístí spuštění.

Vygenerovaný soubor se pak ještě [digitálně podepsal](./signtool.bat) důvěryhodným code-signing certifikátem (tehdy důvěryhodná StartSSL), aby mu důvěřovaly ochranné mechanismy ve Windows.

Sonda je spuštěna v příkazovém řádku, takže je silně oddělena od uživatele. Nicméně sonda potřebuje znát velmi přesně, o jakého uživatele se jedná - proto [otevře browser s webovou aplikací](./sysinfo/audit/audit.php#L59), ve které by měl být uživatel přihlášen z předchozího kroku a předání jí unikátní kód. Tím se obě části aplikace bezpečně spárují.

Následuje sekvence těžení dat ze systému a jejich odesílání na server. Komunikace se serverem je zabezpečena [velmi striktní politikou TLS](./sysinfo/audit/audit.php#L7), včetně [seznamu certifikátů](./sysinfo/audit/cacert.pem), kterým smí věřit.

Sekvence:

1. [Konfigurace HW a systému pomocí msinfo32](./sysinfo/audit/msinfo.cmd),
2. [Nainstalované aplikace](./sysinfo/audit/installedapp.ps1),
3. [Seriové a produktové číslo stroje](./sysinfo/audit/bios.cmd) - podporuje většina výrobců, zejména notebooků.

## Zajímavosti

Prože aplikace běží s elevovanými právy roota, nemůže se přímo připojovat k prostředí uživatele, proto jsou všechna volání systémových příkazů extrahována do samostatných scriptů, které jsou volány.

Seznam nainstalovaných aplikací na Windows 7 nebylo možné získaz jinak, než čtením z Registru Windows. A z příkazové rádky se registr nejlépe (nikoliv dobře, ale pouze jsou všechny ostatní varianty jen horší) vypisuje pomocí PowerShellu, což je taková šílenost, ne které se [spouštěný příkaz zabaluje do base64](./sysinfo/audit/audit.php#L244) :) Ale funguje to.

Ačkoliv... další zajímavostí je, jak velký rozdíl je mezi 32- a 64-bitovými Windows. Je třeba detekci udělat až [zkušebním čtením ze 64bitové větve](./sysinfo/audit/installedapp.ps1#L3). Bohužel aplikace samotná nemusí mí správné informace o tom, kde běží – PHP dodané v balíčku bylo úmyslně 32bitové, takže podává zkreslené informace.
