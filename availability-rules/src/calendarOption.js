import { useState } from 'react';
import TotalTimeInput from './totalTimeInput';
import './calendarOptions.scss';
function CalendarOptions () {
  const [vz_am_duration, setVz_am_duration] = useState(70);
  const [vz_am_rest, setVz_am_rest] = useState(10);
  const [calendarIsEnabled, setCalendarIsEnabled] = useState(true);
  const [userCanSchedueXDaysInAdvance, setUserCanSchedueXDaysInAdvance] = useState(60);
  const [requiresInvite, setRequiresInvite] = useState(true);

  async function copyInviteLink(e) {
    e.preventDefault();
    // add to clipboard
    const link = "example";
    try {
      await navigator.clipboard.writeText(link);
      console.log('Page URL copied to clipboard');
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
                  name="vz-am__enable-calendar" />
              <span>
                Enable Calendar
              </span>
          </label>
        </div>

        <div className="vz-am__calendar-option">
          <label>
            Minimum Meeting Size
          </label>
          <TotalTimeInput minutes={vz_am_duration}
                          name="vz_am_calendar-duration"
                          setMinutes={setVz_am_duration} />
        </div>
        <div className="vz-am__calendar-option">
          <label>
            Rest between meetings
          </label>
          <TotalTimeInput minutes={vz_am_rest}
                          name="vz_am_calendar-rest"
                          setMinutes={setVz_am_rest} />
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
                    name="vz-am__enable-calendar" />
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
