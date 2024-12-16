import { useState } from 'react';
import TotalTimeInput from './totalTimeInput';
import './calendarOptions.scss';
function CalendarOptions () {
  const [vz_am_duration, setVz_am_duration] = useState(70);
  const [vz_am_rest, setVz_am_rest] = useState(10);
  const vz_am_invitation_template = {
    uses: 1, // number of times this invitation can be used
    expiration_date: new Date(new Date().getTime() + 86400000), // date when this invitation expires, default: tomorrow
    allow_multiple: false, // whether this invitation can be used more than once
    prevent_overschedule: true, // whether this invitation can be used to schedule multiple appointments at the same time
    invitation_url: '', // unique identifier for this invitation
    status: 'active' // status of this invitation: active || inactive
  };
  const [vz_am_invitations, setVz_am_invitations] = useState([]);

  function addInvitation () {
    setVz_am_invitations([...vz_am_invitations, vz_am_invitation_template]);
  }

  function updateInvitation (i, name, value) {
    const newInvitations = [...vz_am_invitations];
    newInvitations[i][name] = value;
    setVz_am_invitations(newInvitations);
  }
  function formatDate (date) {
    return date.toISOString().split('T')[0];
  } 

  function deleteInvitation (i) {
    const newInvitations = [...vz_am_invitations];
    newInvitations.splice(i, 1);
    setVz_am_invitations(newInvitations);
  }


  return (
    <div className="vz-am__section">
      <section className="vz-am__calendar-options">
        <header className="vz-am__header">
          <h2>Calendar Options</h2>
        </header>
        <div className="vz-am__calendar-option">
          <label>
            Minimum Appointment Size
          </label>
          <TotalTimeInput minutes={vz_am_duration}
                          name="vz_am_duration"
                          setMinutes={setVz_am_duration} />
        </div>
        <div className="vz-am__calendar-option">
          <label>
            Rest between appointments
          </label>
          <TotalTimeInput minutes={vz_am_rest}
                          name="vz_am_rest"
                          setMinutes={setVz_am_rest} />
        </div>
        
      </section>
      <section className="vz-am__appointment-invitations">
        <header className="vz-am__header">
          <h2>
            Invitations
          </h2>
          <button className="add-rule" 
                  onClick={() => addInvitation() }>
            + New Invitation
          </button>
        </header>
        <ul className="vz-am__invitations__list">
          {vz_am_invitations.map((invitation, i) => (
            <li key={i}>
              <article className="vz-am__invitation">
              <div className="vz-am__invitation__input">
                <button className="vz-am__invitation__delete"
                  onClick={() => deleteInvitation(i)}>
                  Delete
                </button>
              </div>
              <div className="vz-am__invitation__input status">
                <label>
                  <input type="checkbox"
                          onChange={(e) => updateInvitation(i, 'status', e.target.checked ? 'active' : 'inactive')}
                          />
                    <span>
                      Active
                    </span>
                </label>
              </div>
              <div className="vz-am__invitation__input number-of-uses">
                <label>
                  # of uses
                </label>
                <input type="number"
                        onChange={(e) => updateInvitation(i, 'uses', parseInt(e.target.value))}
                       value={invitation.uses} />
              </div>
              <div className="vz-am__invitation__input">
                <label>
                  Expiration Date
                </label>
                <input type="date" 
                        onChange={(e) => updateInvitation(i, 'expiration_date', new Date(e.target.value))}
                       value={formatDate(invitation.expiration_date)} />
              </div>
              <div className="vz-am__invitation__input">
                <label>
                <input type="checkbox" 
                        onChange={(e) => updateInvitation(i, 'allow_multiple', e.target.checked)}
                       checked={invitation.allow_multiple} />
                  Allow Multiple Uses
                </label>
      
                <label>
                <input type="checkbox" 
                        onChange={(e) => updateInvitation(i, 'prevent_overschedule', e.target.checked)}
                       checked={invitation.prevent_overschedule} />
                  Prevent Overschedule
                </label>
              </div>
              <div className="vz-am__invitation__input">
                <label>
                  Invitation Url
                </label>
                <div className="invitation-url">
                  <input type="text" 
                          onChange={(e) => updateInvitation(i, 'invitation_url', e.target.value)}
                        value={invitation.invitation_url} />
                  <button onClick={() => navigator.clipboard.writeText(invitation.invitation_url)}>
                    Copy
                  </button>
                </div>
              </div>
              </article>
            </li>
          ))}
        </ul>
      </section>
    </div>
  );
}

export default CalendarOptions;

// create link to schedule an appointment
// this link may be used more than once
// this link may be used one at a time per user
// this link may be used once, and then it expires
