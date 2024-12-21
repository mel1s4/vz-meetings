
import React, { use, useEffect, useState} from 'react';
import axios from 'axios';
import './App.scss';
import UserMeetings from './UserMeetings/UserMeetings';
import Calendar from './Calendar/Calendar';
import {_vz, formatDate, formatDateReadable } from './translations';
import TimeSlots from './TimeSlots/TimeSlots';

function App({ preview = false }) {
  const [previewMode, setPreviewMode] = useState(preview);
  const [userMeetings, setUserMeetings] = useState([]);
  const [selectedLanguage, setSelectedLanguage] = useState('es');
  const [timeSlotSize, setTimeSlotSize] = useState(30);
  const [calendarId, setCalendarId] = useState(null);
  const [selectedDay, setSelectedDay] = useState(null);
  const [selectedMonth, setSelectedMonth] = useState(null);
  const [selectedYear, setSelectedYear] = useState(null);
  const [monthAvailability, setMonthAvailability] = useState([]);
  const [timeSlots, setTimeSlots] = useState({});
  const [restNonce, setRestNonce] = useState(null);
  const [timeZone, setTimeZone] = useState(null);
  const [visitorTimeZone, setVisitorTimeZone] = useState(
    Intl.DateTimeFormat().resolvedOptions().timeZone
  );
  const [restUrl, setRestUrl] = useState('http://localhost/wp-json/');
  const [selectedTimeSlot, setSelectedTimeSlot] = useState(null);
  const [monthIsLoading, setMonthIsLoading] = useState({});
  const [timeSlotsAreLoading, setTimeSlotsAreLoading] = useState({});
  const [inviteCode, setinviteCode] = useState(null);
  const [isLoggedIn, setIsLoggedIn] = useState(false);
  const [userEmail, setUserEmail] = useState(null);
  const [userName, setUserName] = useState(null);
  const [requiresInvite, setRequiresInvite] = useState(false);
  const [popup, setPopup] = useState({
    open: false,
    message: '',
    type: 'success', // success, error, warning
  });
  const [confirmationIsLoading, setConfirmationIsLoading] = useState(false);
  const [highlightedDatetime, setHighlightedDatetimeVariable] = useState(null);
  const [errorFetching, setErrorFetching] = useState(null);
  const [meetingWasConfirmed, setMeetingWasConfirmed] = useState(false);
  function setHighlightedDateTime(date) {
    if (date) {
      setHighlightedDatetimeVariable(date);
    } else {
      setHighlightedDatetimeVariable(null);
    }
  }

  useEffect(() => {
    const today = new Date();
    setSelectedDay(today.getDate());
    setSelectedMonth(today.getMonth());
    setSelectedYear(today.getFullYear());

    if (window?.vz_calendar_view_params && !previewMode) {
      const { 
        calendar_id,
        rest_url,
        time_zone,
        availability,
        slot_size,
        language,
        rest_nonce,
        meetings,
        invite,
        requires_invite,
        is_logged_in,
       } = window.vz_calendar_view_params;
        setCalendarId(calendar_id);
        setRestUrl(rest_url);
        setTimeZone(time_zone);
        setTimeSlotSize(slot_size);
        setSelectedLanguage(language);
        setRestNonce(rest_nonce);
        const nmA = {};
        nmA[today.getFullYear() + '-' + (today.getMonth() + 1)] = availability?.available_days;
        setMonthAvailability(nmA);
        setUserMeetings(meetings);
        setinviteCode(invite);
        setRequiresInvite(requires_invite);
        setIsLoggedIn(is_logged_in === "true");
    }
  }, []);
  
  

  useEffect(() => {
    if (selectedDay && calendarId) {
      getTimeSlots();
    }
  }
  , [selectedDay, selectedMonth, selectedYear, calendarId]);

  async function getTimeSlots() {
    if ((requiresInvite && !inviteCode) || previewMode) return;
    if (timeSlots && timeSlots[selectedYear] && timeSlots[selectedYear][selectedMonth + 1] && timeSlots[selectedYear][selectedMonth + 1][selectedDay]) {
      return;
    }
    try {

      const pLoading = { ...timeSlotsAreLoading };
      pLoading[selectedYear + '-' + (selectedMonth + 1) + '-' + selectedDay] = true;
      setTimeSlotsAreLoading(pLoading);
      
      const getParams = {
        month: selectedMonth + 1,
        year: selectedYear,
        day: selectedDay,
        calendar_id: calendarId,
        invite: inviteCode,
      };
      const response = await api('timeslots', getParams);
      const newTimeSlots = {
        ...timeSlots
      };

      if (!newTimeSlots[selectedYear]) {
        newTimeSlots[selectedYear] = {};
      }
      if (!newTimeSlots[selectedYear][selectedMonth + 1]) {
        newTimeSlots[selectedYear][selectedMonth + 1] = {};
      }

      
      const nTimeSlots = response.data.timeslots.timeslots;
      newTimeSlots[selectedYear][selectedMonth + 1][selectedDay] = nTimeSlots;
      setTimeSlots(newTimeSlots);
      const pLoading2 = { ...timeSlotsAreLoading };
      pLoading2[selectedYear + '-' + (selectedMonth + 1) + '-' + selectedDay] = false;
      setTimeSlotsAreLoading(pLoading2);


    } catch (error) {
      setErrorFetching('timeslots');
      const pLoading2 = { ...timeSlotsAreLoading };
      pLoading2[selectedYear + '-' + (selectedMonth + 1) + '-' + selectedDay] = false;
      setTimeSlotsAreLoading(pLoading2);
      console.log('error', error);
      setPopup({
        open: true,
        message: 'There was an error fetching the time slots. Please try again later.',
        type: 'error'
      });
      console.error(error);
    }
  }

  useEffect(() => {
    // if selected month is not current month
    const today = new Date();
    const cMonth = today.getMonth();
    if (selectedMonth === cMonth) {
      return;
    }
    if (selectedMonth >= 0 && selectedYear > 2000 && restUrl ) {
      getMonthAvailability(selectedMonth + 1, selectedYear);
    }
  }, [selectedMonth, selectedYear, restUrl]);

  async function getMonthAvailability() {
    if (requiresInvite && !inviteCode) return;
    if (monthAvailability[selectedYear + '-' + (selectedMonth + 1)]) {
      return;
    }
    try {
      const pLoadingMonths = { ...monthIsLoading };
      pLoadingMonths[selectedYear + '-' + (selectedMonth + 1)] = true;
      setMonthIsLoading(pLoadingMonths);
      const params = {
        year: selectedYear,
        month: selectedMonth + 1,
        calendar_id: calendarId,
        invite: inviteCode,
      };
      const response = await api('availability', params);
      const newMonthAvailability = { ...monthAvailability };
      newMonthAvailability[selectedYear + '-' + (selectedMonth + 1)] = response.data.available_days;
      setMonthAvailability(newMonthAvailability);
      
      const pLoadingMonths2 = { ...monthIsLoading };
      pLoadingMonths2[selectedYear + '-' + (selectedMonth + 1)] = false;
      setMonthIsLoading(pLoadingMonths2);
    } catch (error) {
      console.error(error);
      const pLoadingMonths2 = { ...monthIsLoading };
      pLoadingMonths2[selectedYear + '-' + (selectedMonth + 1)] = false;
      setMonthIsLoading(pLoadingMonths2);
      const rInvite = error.response.data.code == "invalid_invite";
      if (rInvite){ 
        if (!requiresInvite) setRequiresInvite(true);
        setErrorFetching('invite_code');
        // ignore if invite is required
      } else {
        setPopup({
          open: true,
          message: _vz('error-fetching-month'),
          type: 'error'
        });
      }

    }
  }

  function getDateTimeInLocale(date, separated = false) {
    const year = date.toLocaleString('default', { year: 'numeric' });
    // const month = date.toLocaleString('default', { month: 'long' });
    const month = _vz('months').split(',')[date.getMonth()];
    const day = date.toLocaleString('default', { day: 'numeric' });
    const time = date.toLocaleString('default', { hour: 'numeric', minute: '2-digit' });
    if (separated) {
      return [`${month} ${day}, ${year}`,`@${time.toLowerCase()}`];
    } else {
      return `${month} ${day}, ${year} @${time.toLowerCase()}`;
    }
  }

  function getDayOfWeek(date) {
    return _vz('weekdays_long').split(',')[date.getDay()];	
  }

  async function api(endpoint, data) {
    const params = {... data, language: selectedLanguage, timezone: visitorTimeZone};
    return await axios.post( restUrl + 'vz-am/v1/' + endpoint, params, {
      headers: {
        'X-WP-Nonce': restNonce
      }
  });
  }

  function lockedTimeSlots() {
    if (meetingWasConfirmed) return true;
    if (requiresInvite && !inviteCode) return true;
    if (inviteCode && errorFetching === 'invite_code') return true;
    console.log('lockedTimeSlots', requiresInvite, inviteCode, errorFetching);
    return false;
  }
  
  async function confirmTimeSlot() {
    if (previewMode) return;
    const data = {
      calendar_id: calendarId,
      date_time: selectedTimeSlot,
      nonce: restNonce,
      invite: inviteCode,
    };
    if(!isLoggedIn) {
      data.user_email = userEmail;
      data.user_name = userName;
    }
    try {
      setConfirmationIsLoading(true);
      const response = await api('confirm', data);
      setPopup({
        open: true,
        message: _vz('meeting-confirmed'),
        type: 'success'
      });
      setUserMeetings([...userMeetings, {
        id: response.data.id,
        date_time: selectedTimeSlot,
        duration: timeSlotSize
      }]);
      setSelectedTimeSlot(null);
      // remove slot from timeslots
      setTimeSlots(null);
      setConfirmationIsLoading(false);
      setMeetingWasConfirmed(true);
    } catch (error) {
      console.error(error);
      setConfirmationIsLoading(false);
      let message = _vz('fetching-error')
      if (error.response.data.code === 'email_exists') {
        message = _vz('email-exists-error');
      }
      setPopup({
        open: true,
        message,
        type: 'error'
      });
    }
  }

  function userIsLoggedIn() {
    return window.vz_calendar_view_params?.is_logged_in;
  }

  function formatSelectedTimeSlot() {
    console.log('formatSelectedTimeSlot', selectedTimeSlot);
    return "helo";
  }

  return (
    <section className={`vz-time-slot-selection ${preview ? '--vz-is-preview' : ''}`}>

      {(userMeetings.length > 0 && !previewMode) && (
        <UserMeetings userMeetings={userMeetings}
                      setHighlightedDateTime={setHighlightedDateTime}
                      getDateTimeInLocale={getDateTimeInLocale}
                      getDayOfWeek={getDayOfWeek}
        />
      )}

      {(requiresInvite && !inviteCode) && (
        <div className="vz-am__requires-invite">
          <h3 className="vz-am__title"> 
            {_vz('requires-invite')}
          </h3>
        </div>
      )}

      {(errorFetching === 'invite_code' && inviteCode && !meetingWasConfirmed) && (
        <div className="vz-am__requires-invite">
          <h3 className="vz-am__title"> 
            {_vz('invalid-invite')}
          </h3>
        </div>
      )}

      {((meetingWasConfirmed && requiresInvite && inviteCode) || (meetingWasConfirmed && !requiresInvite)) && (
        <div className="vz-am__requires-invite">
          <h3 className="vz-am__title"> 
            {_vz('meeting-confirmation')}
          </h3>
        </div>
      )}


      <Calendar 
        selectedMonth={selectedMonth}
        selectedYear={selectedYear}
        selectedDay={selectedDay}
        setSelectedDay={setSelectedDay}
        monthIsLoading={monthIsLoading}
        monthAvailability={monthAvailability}
        highlightedDatetime={highlightedDatetime}
        setSelectedMonth={setSelectedMonth}
        setSelectedYear={setSelectedYear}
        formatDate={formatDate}
      />

      <TimeSlots
        timeSlots={timeSlots}
        selectedYear={selectedYear}
        selectedMonth={selectedMonth}
        selectedDay={selectedDay}
        selectedTimeSlot={selectedTimeSlot}
        setSelectedTimeSlot={setSelectedTimeSlot}
        formatDate={formatDate}
        timeSlotSize={timeSlotSize}
        timeSlotsAreLoading={timeSlotsAreLoading}
        timeZone={timeZone}
        visitorTimeZone={visitorTimeZone}
        lockedTimeSlots={lockedTimeSlots}
      />

      {((selectedTimeSlot || previewMode) &&
        <div className="vz-meeting-confirmation">
          <div className="vz-am__confirmation-box">
            <h2 className="vz-meeting-confirmation">
              {_vz('meeting-confirmation')}
            </h2>
            <p>
              {selectedTimeSlot ? `${_vz('you-selected')} ${formatDateReadable(selectedTimeSlot)} ${_vz('for-meeting')}` : _vz('please-select-time-slot')}
            </p>
            { (!isLoggedIn) && (
              <div className="vz-am__registration-form">
                <div>
                <label>
                  {_vz('your-email')}
                </label>
                <input type="email"
                        value={userEmail}
                        onChange={(e) => setUserEmail(e.target.value)} 
                        placeholder={_vz('email-placeholder')} />
                </div>
                <div>
                <label>
                  {_vz('your-name')}
                </label>
                <input type="text"
                        value={userName}
                        onChange={(e) => setUserName(e.target.value)} 
                        placeholder={_vz('name-placeholder')} />
                </div>
              </div>
            )}
            <button 
              disabled={confirmationIsLoading}
              className="vz-am__confirmation-button"
              onClick={() => confirmTimeSlot()}>
              {_vz('confirm')}
            </button>
          </div>
        </div>
      )}

      {(popup.open) && (
        <div className={`vz-popup --${popup.type}`}>
          <p>{popup.message}</p>
          <button onClick={() => setPopup({ ...popup, open: false })}>
            {_vz('Ok')}
          </button>
        </div>
      )}

    </section>
  );
}

export default App;
