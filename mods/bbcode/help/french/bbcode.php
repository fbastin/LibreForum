<html>
  <head>
    <title>Aide sur le formatage</title>
    <link rel="stylesheet" type="text/css" href="<?php print $GLOBALS["PHORUM"]["http_path"] ?>/mods/bbcode/help/help.css"/>
  </head>
  <body>
    <h2>Aide sur le formatage des messages</h2>

    Ce forum utilise la syntaxe <strong>Markdown</strong> pour le formatage des
    messages. Les fonctions sans &eacute;quivalent Markdown utilisent du HTML en ligne.

    <h3>Gras : **...**<br/>
        Italique : *...*<br/>
        Barr&eacute; : ~~...~~</h3>
    Exemples :<br/><br/>
    <tt>
    **Ce texte est en gras**<br/>
    *Ce texte est en italique*<br/>
    ~~Ce texte est barr&eacute;~~<br/>
    **Texte *gras et italique* combin&eacute;s**
    </tt><br/><br/>
    R&eacute;sultat :<br/><br/>
    <b>Ce texte est en gras</b><br/>
    <i>Ce texte est en italique</i><br/>
    <strike>Ce texte est barr&eacute;</strike><br/>
    <b>Texte <i>gras et italique</i> combin&eacute;s</b>

    <h3>Soulign&eacute; : &lt;u&gt;...&lt;/u&gt;</h3>
    Le soulign&eacute; n'existe pas en Markdown. Utilisez la balise HTML :<br/><br/>
    <tt>&lt;u&gt;Ce texte est soulign&eacute;&lt;/u&gt;</tt><br/><br/>
    R&eacute;sultat : <u>Ce texte est soulign&eacute;</u>

    <h3>Exposant : &lt;sup&gt;...&lt;/sup&gt;<br/>
        Indice : &lt;sub&gt;...&lt;/sub&gt;</h3>
    Utile pour les formules comme 2<sup>4</sup> = 16 ou H<sub>2</sub>O :<br/><br/>
    <tt>
    2&lt;sup&gt;4&lt;/sup&gt; = 16<br/>
    H&lt;sub&gt;2&lt;/sub&gt;O
    </tt><br/><br/>
    R&eacute;sultat : 2<sup>4</sup> = 16, H<sub>2</sub>O

    <h3>Lien vers un site : [texte](url)<br/>
        Lien vers un email : &lt;adresse@email.com&gt;</h3>
    Exemples :<br/><br/>
    <tt>
    [Visitez Tireur.org](https://www.tireur.org)<br/>
    &lt;contact@tireur.org&gt;
    </tt><br/><br/>
    R&eacute;sultat :<br/><br/>
    <a href="https://www.tireur.org">Visitez Tireur.org</a><br/>
    <a href="mailto:contact@tireur.org">contact@tireur.org</a>

    <h3>Image depuis le web : ![description](url)</h3>
    Exemple :<br/><br/>
    <tt>
    ![Logo](https://www.tireur.org/images/logo-site.png)
    </tt><br/><br/>
    R&eacute;sultat :<br/><br/>
    <img src="<?php print $GLOBALS["PHORUM"]["http_path"] ?>/mods/bbcode/help/thumbsup.gif" alt="Pouce lev&eacute;" />

    <h3>Citation : &gt; ...</h3>
    Ajoutez <tt>&gt;</tt> au d&eacute;but de chaque ligne pour citer un texte :<br/><br/>
    <tt>
    &gt; **Shakespeare** a &eacute;crit :<br/>
    &gt; &Ecirc;tre ou ne pas &ecirc;tre,<br/>
    &gt; telle est la question.
    </tt><br/><br/>
    R&eacute;sultat :<br/><br/>
    <blockquote style="border-left: 3px solid #ccc; padding-left: 10px; margin-left: 5px; color: #555;">
    <b>Shakespeare</b> a &eacute;crit :<br/>
    &Ecirc;tre ou ne pas &ecirc;tre,<br/>
    telle est la question.
    </blockquote>

    <h3>Bloc de code : ```...```</h3>
    Pour du code, de l'ASCII art ou du texte pr&eacute;format&eacute;, encadrez-le avec
    trois accents graves sur des lignes s&eacute;par&eacute;es :<br/><br/>
    <tt>
    ```<br/>
    function hello() {<br/>
    &nbsp;&nbsp;return "world";<br/>
    }<br/>
    ```
    </tt><br/><br/>
    R&eacute;sultat :<br/>
    <pre style="border: 1px solid #dde; background-color: #ffe; padding: 5px 10px;">function hello() {
  return "world";
}</pre>
    Pour du code en ligne, utilisez un seul accent grave : <tt>`code`</tt> &rarr; <code style="background:#f6f8fa; padding:1px 4px; border-radius:3px;">code</code>

    <h3>Ligne horizontale : ---</h3>
    Trois tirets ou plus sur une ligne seule cr&eacute;ent un s&eacute;parateur :<br/><br/>
    <tt>---</tt><br/><br/>
    R&eacute;sultat :
    <hr/>

    <h3>Titres : # ... ## ... ### ...</h3>
    <tt>
    # Titre niveau 1<br/>
    ## Titre niveau 2<br/>
    ### Titre niveau 3
    </tt><br/><br/>
    R&eacute;sultat :<br/>
    <span style="font-size: x-large; font-weight: bold;">Titre niveau 1</span><br/>
    <span style="font-size: large; font-weight: bold;">Titre niveau 2</span><br/>
    <span style="font-size: medium; font-weight: bold;">Titre niveau 3</span>

    <h3>Listes &agrave; puces : * ou -<br/>
        Listes num&eacute;rot&eacute;es : 1. 2. 3.</h3>
    <tt>
    * Premier &eacute;l&eacute;ment<br/>
    * Deuxi&egrave;me &eacute;l&eacute;ment<br/>
    <br/>
    1. &Eacute;tape un<br/>
    2. &Eacute;tape deux<br/>
    3. &Eacute;tape trois
    </tt><br/><br/>
    R&eacute;sultat :<br/>
    <ul><li>Premier &eacute;l&eacute;ment</li><li>Deuxi&egrave;me &eacute;l&eacute;ment</li></ul>
    <ol><li>&Eacute;tape un</li><li>&Eacute;tape deux</li><li>&Eacute;tape trois</li></ol>

    <h3>Tableaux</h3>
    <tt>
    | Calibre | Poids (gr) | V0 (m/s) |<br/>
    |---|---|---|<br/>
    | .308 Win | 168 | 790 |<br/>
    | 6.5 CM | 140 | 850 |
    </tt><br/><br/>
    R&eacute;sultat :<br/><br/>
    <table cellpadding="3" cellspacing="0" border="1" style="border-collapse:collapse; border-color:#ddd;">
    <tr><th>Calibre</th><th>Poids (gr)</th><th>V0 (m/s)</th></tr>
    <tr><td>.308 Win</td><td>168</td><td>790</td></tr>
    <tr><td>6.5 CM</td><td>140</td><td>850</td></tr>
    </table>

    <h3>Fonctions suppl&eacute;mentaires (HTML)</h3>
    Ces fonctions n'ont pas d'&eacute;quivalent Markdown et utilisent des balises HTML :<br/><br/>
    <table cellpadding="4" cellspacing="0" border="0">
    <tr><th>Fonction</th><th>Syntaxe</th><th>R&eacute;sultat</th></tr>
    <tr><td>Couleur</td><td><tt>&lt;span style="color:red"&gt;texte&lt;/span&gt;</tt></td><td><span style="color:red">texte</span></td></tr>
    <tr><td>Taille</td><td><tt>&lt;span style="font-size:large"&gt;texte&lt;/span&gt;</tt></td><td><span style="font-size:large">texte</span></td></tr>
    <tr><td>Centrer</td><td><tt>&lt;center&gt;texte&lt;/center&gt;</tt></td><td><center>texte</center></td></tr>
    <tr><td>Petite taille</td><td><tt>&lt;small&gt;texte&lt;/small&gt;</tt></td><td><small>texte</small></td></tr>
    <tr><td>Aligner &agrave; droite</td><td><tt>&lt;div align="right"&gt;texte&lt;/div&gt;</tt></td><td style="text-align:right;">texte</td></tr>
    </table>

    <br/><br/>
  </body>
</html>
