{! This template implements the default displaying of the Google }
{! map in the user's profile screen. }

{IF MOD_OPENSTREETMAP}
  <div class="generic" style="border-top: none">
    <div style="padding: 10px">
      <strong>{LANG->mod_openstreetmap->ProfileTitle}</strong><br/>
      <div style="height: 300px; border: 1px solid #aaa">
        {MOD_OPENSTREETMAP}
      </div>
    </div>
  </div>
{/IF}

