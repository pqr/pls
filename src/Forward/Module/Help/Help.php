<?php
\Light\Response::setPageHeadHtml('Help');
\Light\Response::setPageTitle('Help');
?>

<?php
if (\Light\FilesFolder::isWindowsSharedFolderAllowed()): ?>
<h2>Полезная информация</h2>
<ul><li><?= \Light\FilesFolder::getLink('Help', 'Открыть папку с файлами с полезной информацией') ?></li></ul>
<?php endif; ?>


<?php
$pdfs = glob(LIGHT_DIR_PUBLIC . "/Module/Help/*.pdf");
if ($pdfs): ?>

    <h2>Инструкции в PDF</h2>
<ul>
    <?php
    foreach ($pdfs as $pdf):
        //$pdf = \Light\File::decode($pdf);
        $pdf = basename($pdf);
        ?>
        <li><a href="Module/Help/<?= htmlspecialchars(rawurlencode($pdf)) ?>" target="_blank"><?= $pdf ?></a></li>
        <?php
    endforeach;
endif;
?>
</ul>

<h2>Цвета</h2>
<ul style="list-style: none;">
    <?php
    $colorHints = \Light\Field\FieldColor::getColorHints();
    for ($i = 1; $i <= 11; $i++):
        ?>
        <li style="margin-bottom: 3px;"><span class="color-span color<?= $i ?>"><?= $i ?></span>
            <?= $colorHints['ColorHint' . $i] ?>
        </li>
    <?php
    endfor;
    ?>
</ul>

<h2>Настройка работы папок</h2>
<ul>
    <li><a href="#firefox">Настройка Firefox</a></li>
    <li><a href="#ie">Настройка Internet Explorer</a></li>
</ul>

<a name="firefox"></a>
<h2>Настройка Firefox</h2>
<ol>
    <li>Для начала нужно авторизоваться на сервере, открыв папку FilesFolder вручную. Для этого откройте "Мой Компьютер" и в строке
        адерса наберите адрес папки на сервере: <b>\\192.168.17.7\FilesFolder</b> (при подключении по VPN адрес будет <b>\\10.20.1.1\FilesFolder</b>)
        - появится диалоговое окно ввода логина и пароля.
        Здесь нужно ввести логин и пароль от сервера, чтобы получить доступ к папке.
        <br><img src="Module/Help/explorer.png"></li>
    <li>Скачайте файл <a href="Module/Help/firefox_folders.zip">firefox_folders.zip</a>, сохранив, например, на рабочем
        столе
    </li>
    <li>
        В окне Firefox в верхнем правом углу раскройте меню как на скриншоте и нажмите кнопку с икнокой вопроса
        <br><img src="Module/Help/ffsettings1.png">
        <br>затем выберите пункт "Информация для решения проблем"
        <br><img src="Module/Help/ffsettings2.png">
    </li>
    <li>
        В открывшемся окне нажмите на кнопку "Показать папку" - откроется окно проводника с профилем текущего пользователя Fierefox. Это окно проводника нужно оставить, а сам Firefox закрыть
        <br><img src="Module/Help/ffsettings3.png">
    </li>
    <li>
        Из скаченного ранее firefox_folders.zip нужно вынуть файл user.js и скопировать его в папку профиля, которая была открыта на предыдущем шаге. Если файл уже существует, нужно подтвердить замену.
        <br><img src="Module/Help/copyuserjs.png">
    </li>
    <li>Запустить Firefox заново, перейти в раздел Shipments и кликнуть на любую иконку папки - появится дилоговое окно в котором
        нажать кнопку "Выбрать..."<br><img
            src="Module/Help/ffdialog1.png"></li>
    <li>Появится второе диалоговое окно: выбор файла приложения. Нужно перейти в папку C:\Windows и выбрать файл
        explorer.exe, нажать
        "Открыть"<br><img
            src="Module/Help/ffdialog2.png"></li>
    <li>После возвращения к предыдущему диалоговому окну нужно поставить галочку внизу "Запомнить мой выбор для file
        ссылок" и
        нажать OK<br><img
            src="Module/Help/ffdialog3.png"></li>
    <li>Папки открываются!</li>
</ol>

<a name="ie"></a>
<h2>Настройка Internet Explorer</h2>
<ol>
    <li>Для начала нужно авторизоваться на сервере, открыв папку FilesFolder вручную. Для этого откройте "Мой Компьютер" и в строке
        адерса наберите адрес папки на сервере: <b>\\192.168.17.7\FilesFolder</b> (при подключении по VPN адрес будет <b>\\10.20.1.1\FilesFolder</b>)
        - появится диалоговое окно ввода логина и пароля.
        Здесь нужно ввести логин и пароль от сервера, чтобы получить доступ к папке.
        <br><img src="Module/Help/explorer.png"></li>
    <li>В окне Internet Explorer нажать кнопку Alt - появится строка меню сверху. В ней выборать пункт Сервис ->
        Свойства браузера<br><img
            src="Module/Help/iemenu.png"></li>
    <li>В открывшемся диалоговом окне перейти на вкладку Безопасность, выбрать пункт Надёжные сайты и нажать кнопку
        Сайты<br><img src="Module/Help/iesecruritytab.png"></li>
    <li>В список надёжных сайтов нужно добавить 192.167.17.7 и <b>снять</b> галочку внизу, нажать Закрыть<br><img
            src="Module/Help/iesites.png"></li>
    <li>Папки открываются!</li>
</ol>
