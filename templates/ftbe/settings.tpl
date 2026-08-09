{! --- defines are used by the engine and vars are used by the template --- }

{! --- How many px to indent for each level --- }
{DEFINE indentmultiplier 20}

{! --- This is used to load the message-bodies in the message-list for that template if set to 1 --- }
{DEFINE bodies_in_list 0}

{! --- This is used the number of page numbers shown on the list page in the paging section (eg. 1 2 3 4 5) --- }
{DEFINE list_pages_shown 5}

{! --- Define on what pages notifications should be displayed ---- }
{DEFINE show_notify_for_pages "index,list,cc"}

{! --- Apply some compression to the template data. This feature is      --- }
{! --- implemented by Phorum's template parsing code. Possible values    --- }
{! --- for this setting are:                                             --- }
{! --- 0 - Apply no compression at all.                                  --- }
{! --- 1 - Remove white space at start of lines and empty lines.         --- }
{! --- 2 - Additionally, remove some extra unneeded white space and HTML --- }
{!         comments. Note that this makes the output quite unreadable,   --- }
{!         so it is mainly useful for a production environment.          --- }
{DEFINE tidy_template 0}

{! -- This is the image for the gauge bar to show how full the PM box is -- }
{VAR gauge_image "templates/emerald/images/gauge.gif"}

{! -- Fonts -- }

{VAR default_font "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Helvetica Neue', Arial, sans-serif"}

{VAR font_xx_large  "1.35rem"}
{VAR font_x_large   "1.15rem"}
{VAR font_large     "1rem"}
{VAR base_font_size "0.95rem"}
{VAR font_small     "0.85rem"}
{VAR font_x_small   "0.8rem"}
{VAR font_xx_small  "0.75rem"}

{! -- The maximum width of the Phorum content (the div with id "phorum")  -- }
{VAR max_width "100%"}  {! -- CSS size values allowed. No effect in MSIE 6 }
{VAR max_width_ie "100%"} {! -- px width values allowed. Sets max MSIE 6 width }

{! -- Logo size (images/logo.png). Update this if you replace the logo.png -- }
{VAR logo_width     "468"}
{VAR logo_height    "60"}

{! -- colors -- }
{VAR body_background_color "#fdfdfd"}
{VAR default_font_color "#2c3e50"}
{VAR default_background_color "#ffffff"}
{VAR gen_background_color "#f6f8fa"}
{VAR alt_background_color "#f6f8fa"}
{VAR highlight_background_color "#e8f0f6"}
{VAR th_background_color "#141D26"}
{VAR border_color "#e0e4e8"}
{VAR border_font_color "#ffffff"}
{VAR quote_border_color "#e0e4e8"}
{VAR pre_border_color "#e1e4e8"}
{VAR pre_background_color "#f6f8fa"}
{VAR link_color "#1f6a9c"}
{VAR link_hover_color "#2980b9"}
{VAR new_color "#2C3E50"}
{VAR logo_background_color "#ffffff"}
{VAR breadcrumb_border_color "#e0e4e8"}
{VAR post_moderation_background_color "#f6f8fa"}
{VAR information_border_color "#1f6a9c"}
{VAR information_background_color "#eaf2f8"}
{VAR warning_border_color "#A76262"}
{VAR warning_background_color "#FFD1D1"}
{VAR span_addition_background_color "#e8f0e4"}
{VAR span_addition_font_color "#000000"}
{VAR span_removal_background_color "#ffeaea"}
{VAR span_removal_font_color "#000000"}
{VAR message_background_color "#ffffff"}

{! -- Background Images -- }
{VAR header_background_image ""}
{VAR top_background_image ""}
{VAR message_background_image ""}

{! -- Modern Theme Compatibility Fallbacks (prevents PHP warnings) -- }
{VAR errorfontcolor "#e74c3c"}
{VAR navbackcolor "var(--color-bg)"}
{VAR navhoverbackcolor "var(--color-surface-hover)"}
{VAR navhoverlinkcolor "var(--color-accent-light)"}
{VAR navtextweight "normal"}
{VAR tablebordercolor "var(--color-border)"}
{VAR navfont "var(--font-sans)"}
{VAR navfontsize "0.88rem"}

{! -- Suite des mêmes secours, laissés vides à dessein.                      -- }
{! -- css.tpl référence ces 25 clés, que rien n'a jamais définies : elles se -- }
{! -- rendaient donc en chaîne vide, et le forum est stylé depuis           -- }
{! -- /css/tireur.min.css, chargé après. Les définir vides laisse la        -- }
{! -- feuille servie identique à l'octet près et supprime l'avertissement.  -- }
{! -- Leur rendre de vraies valeurs relève d'un choix de rendu, pas d'un    -- }
{! -- correctif : ce serait rappliquer un habillage absent depuis toujours. -- }
{VAR altbackcolor ""}
{VAR altlisttextcolor ""}
{VAR backcolor ""}
{VAR defaultfont ""}
{VAR defaultfontsize ""}
{VAR defaulttextcolor ""}
{VAR forumwidth ""}
{VAR headerbackcolor ""}
{VAR headerfont ""}
{VAR headerfontsize ""}
{VAR headertextcolor ""}
{VAR headertextweight ""}
{VAR largefont ""}
{VAR largefontsize ""}
{VAR listlinecolor ""}
{VAR listmodlinkcolor ""}
{VAR listpagelinkcolor ""}
{VAR narrowtablewidth ""}
{VAR navtextcolor ""}
{VAR newflagcolor ""}
{VAR okmsgfontcolor ""}
{VAR smallfontsize ""}
{VAR tablewidth ""}
{VAR tinyfont ""}
{VAR tinyfontsize ""}


