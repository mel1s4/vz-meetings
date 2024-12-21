import { useState, useEffect } from 'react';
import TotalTimeInput from './totalTimeInput';
import './calendarOptions.scss';
import api from './api';

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
  calendarId,
}) {
  const [CopyWasTriggered, setCopyWasTriggered] = useState(false);
  async function copyInviteLink(e) {
    e.preventDefault();
    try {
      const params = {
        calendar_id: calendarId
      };
      const response = await api.post('invite_link', params);
      const link = response.data.invite.invite_link;
      await navigator.clipboard.writeText(link);
      setCopyWasTriggered(true);
    } catch(e) {
      console.log('Failed to copy page URL to clipboard');
    }

    setTimeout(() => {
      setCopyWasTriggered(false);
    }, 3000);
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
                  defaultChecked={calendarEnabled} />
               <span className={calendarEnabled ? '--active' : ''}>
                Enable Calendar
              </span>
            <input type="hidden"
                      name="vz_am_enabled" 
                      value={calendarEnabled} />
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
                      defaultChecked={requiresInvite} />
                <span className={requiresInvite ? '--active' : ''}>
                  Requires Invite 
                </span>
                <input type="hidden"
                        name="vz_am_requires_invite" 
                        value={requiresInvite} />
            </label>
            { requiresInvite && 
              <button className="vz-am__copy-invite-link"
                      disabled={CopyWasTriggered}
                      onClick={e => copyInviteLink(e)}>
                {
                  CopyWasTriggered
                    ? 'Copied!'
                    : 'Copy Invite Link'
                }
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
