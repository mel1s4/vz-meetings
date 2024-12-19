import { useState, useEffect } from 'react';
import TotalTimeInput from './totalTimeInput';
import './calendarOptions.scss';
import axios from 'axios';

function CalendarOptions ({
  maxDaysInAdvance,
  setMaxDaysInAdvance,
  Rest,
  setRest,
  Duration, 
  setDuration,
  calendarEnabled,
  setCalendarEnabled,
  requiresInvite,
  setRequiresInvite,
  restUrl,
  setRestUrl,
  restNonce,
  calendarId,
}) {
  const [copyLinkIsLoading, setCopyLinkIsLoading] = useState(false);
  async function copyInviteLink(e) {
    e.preventDefault();
    // add to clipboard
    const link = "example";
    try {
      setCopyLinkIsLoading(true);
      const data = {
        calendar_id: calendarId
      };
      const response = await axios.get( restUrl + 'vz-am/v1/confirm_meeting', data, {
        // headers: {
        //   'X-WP-Nonce': restNonce
        // }
      });
      console.log(response);

      // await navigator.clipboard.writeText(link);
      // console.log('Page URL copied to clipboard');
    } catch(e) {
      console.log('Failed to copy page URL to clipboard');
    }
  }

  return (
    <div className="vz-am__section">
      <section className="vz-am__calendar-options">
        <header className="vz-am__header">
          <h2>Calendar Options</h2>
        </header>
        
        <div className="vz-am__calendar-option">
          <label>
            Enable
          </label>
          <label className="vz-toggle-switch">
            <input type="checkbox"
                  onClick={e => setCalendarEnabled(e.target.checked)}
                  checked={calendarEnabled}
                  name="vz_am_enabled" />
              <span>
                Enable Calendar
              </span>
          </label>
        </div>

        <div className="vz-am__calendar-option">
          <label>
            Minimum Meeting Size
          </label>
          <TotalTimeInput minutes={Duration}
                          name="vz_am_duration"
                          setMinutes={setDuration} />
        </div>
        <div className="vz-am__calendar-option">
          <label>
            Rest between meetings
          </label>
          <TotalTimeInput minutes={Rest}
                          name="vz_am_rest"
                          setMinutes={setRest} />
        </div>
        <div className="vz-am__calendar-option max-days-in-advance">
          <label>
            Maximum days in advance
          </label>
          <input name="vz_am_maximum_days_in_advance"
                type="number"
                onChange={e => setMaxDaysInAdvance(e.target.value)}
                value={maxDaysInAdvance}
                 />
        </div>

        
        <div className="vz-am__calendar-option invite-link">
          <p>
            Requires Invite link
          </p>
          <div className="invite-link__container">
            <label className="vz-toggle-switch">
              <input type="checkbox"
                    onClick={e => setRequiresInvite(e.target.checked)}
                    checked={requiresInvite}
                    name="vz_am_requires_invite" />
                <span>
                  Requires Invite 
                </span>
            </label>
            { requiresInvite && 
              <button className="vz-am__copy-invite-link"
                      onClick={e => copyInviteLink(e)}>
                Copy Link
              </button>
            }
          </div>
        </div>
      </section>
    </div>
  );
}

export default CalendarOptions;

// create link to schedule an meeting
// this link may be used more than once
// this link may be used one at a time per user
// this link may be used once, and then it expires
