
import React, { use, useEffect, useState} from 'react';
import axios from 'axios';
import './App.scss';
import localizedStrings from './localized_strings.json';

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
  const [popup, setPopup] = useState({
    open: false,
    message: '',
    type: 'success', // success, error, warning
  });
  const [confirmationIsLoading, setConfirmationIsLoading] = useState(false);

  function _vz(txt) {
    const lang = selectedLanguage.substring(0, 2);
    if (localizedStrings[txt] && localizedStrings[txt][lang]) {
      return localizedStrings[txt][lang];
    }
    console.log(`No localized string for ${txt}`);
    return txt;
  }

  useEffect(() => {
    const today = new Date();
    // setCalendarId(window?.calendar_params?.calendar_id);
    setCalendarId(19);
    setSelectedDay(today.getDate());
    setSelectedMonth(today.getMonth());
    setSelectedYear(today.getFullYear());
    const exampleTimeSlot = {
      '11:00': true,
      '11:48': true,
      '12:36': true,
      '13:24': true,
      '14:12': true,
      '15:00': true,
      '15:48': true,
      '16:36': true,
    };
    const exampleMeetings = [
        {
            "id": 36,
            "date_time": "2024-12-18T23:00:00.000Z",
            "duration": "45"
        },
        {
            "id": 35,
            "date_time": "2024-12-13T23:00:00.000Z",
            "duration": "45"
        },
        {
            "id": 34,
            "date_time": "2024-12-20T00:36:00.000Z",
            "duration": "45"
        }
    ];
    const exampleTimeSlots = {};
    exampleTimeSlots[today.getFullYear()] = {};
    exampleTimeSlots[today.getFullYear()][today.getMonth() + 1] = {};
    exampleTimeSlots[today.getFullYear()][today.getMonth() + 1][today.getDate()] = exampleTimeSlot;
    setTimeSlots(exampleTimeSlots);
    setUserMeetings(exampleMeetings);
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
        setTimeSlots(availability?.timeslots);
        setUserMeetings(meetings);
    }
  }, []);

  function selectedDateFormatted() {
    if (
      selectedDay >= 0 &&
      selectedMonth >= 0 &&
      selectedYear > 2000
    ) {
      return `${Month(selectedMonth)} ${selectedDay}, ${selectedYear}`;
    }
    return
  }

  
  useEffect(() => {
    if (selectedMonth >= 0 && selectedYear > 2000 && restUrl ) {
      getMonthAvailability(selectedMonth + 1, selectedYear);
    }
  }, [selectedMonth, selectedYear, restUrl]);

  useEffect(() => {
    if (selectedDay && calendarId) {
      getTimeSlots();
    }
  }
  , [selectedDay, selectedMonth, selectedYear, calendarId]);

  async function getTimeSlots() {
    if (previewMode) return;
    if (timeSlots[selectedYear] && timeSlots[selectedYear][selectedMonth + 1] && timeSlots[selectedYear][selectedMonth + 1][selectedDay]) {
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
        calendar_id: calendarId
      };
      const response = await axios.get( restUrl + 'vz-am/v1/timeslots', { params: getParams });
      const newTimeSlots = {
        ...timeSlots
      };

      if (!newTimeSlots[selectedYear]) {
        newTimeSlots[selectedYear] = {};
      }
      if (!newTimeSlots[selectedYear][selectedMonth + 1]) {
        newTimeSlots[selectedYear][selectedMonth + 1] = {};
      }
      newTimeSlots[selectedYear][selectedMonth + 1][selectedDay] = response.data.timeslots;
      setTimeSlots(newTimeSlots);
      
      const pLoading2 = { ...timeSlotsAreLoading };
      pLoading2[selectedYear + '-' + (selectedMonth + 1) + '-' + selectedDay] = false;
      setTimeSlotsAreLoading(pLoading2);


    } catch (error) {
      const pLoading2 = { ...timeSlotsAreLoading };
      pLoading2[selectedYear + '-' + (selectedMonth + 1) + '-' + selectedDay] = false;
      setTimeSlotsAreLoading(pLoading2);
      setPopup({
        open: true,
        message: 'There was an error fetching the time slots. Please try again later.',
        type: 'error'
      });
      console.error(error);
    }
  }

  async function getMonthAvailability() {
    if (monthAvailability[selectedYear + '-' + (selectedMonth + 1)]) {
      return;
    }
    try {
      const pLoadingMonths = { ...monthIsLoading };
      pLoadingMonths[selectedYear + '-' + (selectedMonth + 1)] = true;
      setMonthIsLoading(pLoadingMonths);
      const getParams = {
        year: selectedYear,
        month: selectedMonth + 1,
        calendar_id: calendarId
      };
      const response = await axios.get( restUrl + 'vz-am/v1/availability', { params: getParams });
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
      setPopup({
        open: true,
        message: 'There was an error fetching the availability. Please try again later.',
        type: 'error'
      });

    }
  }

  function formatDate(date = new Date()) {
    const year = date.toLocaleString('default', { year: 'numeric' });
    const month = date.toLocaleString('default', { month: '2-digit' });
    const day = date.toLocaleString('default', { day: '2-digit' });
    return [year, month, day].join('-');
  }

  function nextMonth (e) {
    e.preventDefault();
    if (selectedMonth === 11) {
      setSelectedYear(selectedYear + 1);
      setSelectedMonth(0);
      return;
    }
    setSelectedMonth(selectedMonth + 1);
  }

  function previousMonth (e) {
    e.preventDefault();
    if (selectedMonth === 0) {
      setSelectedYear(selectedYear - 1);
      setSelectedMonth(11);
      return;
    }
    setSelectedMonth(selectedMonth - 1);
  }

  function getDaysInMonth(month, year) {
    return new Date(year, month, 0).getDate();
  }

  function getFirstDayOfMonth(month, year) {    
    return new Date(year, month, 1).getDay();
  }

  function selectTimeSlot(e, start) {
    e.preventDefault();

    const day = selectedDay;
    const month = selectedMonth + 1;
    const year = selectedYear;

    const date = new Date(year, month - 1, day);
    const [hours, minutes] = start.split(':');
    date.setHours(hours);
    date.setMinutes(minutes);
    

    setSelectedTimeSlot(date);
  }

  function isToday(day) {
    const today = new Date();
    const [year, month, date] = formatDate(today).split('-');
    return parseInt(date) === day && parseInt(month) === selectedMonth + 1 && parseInt(year) === selectedYear;
    return false;
  }

  function isAvailable(day) {
    if (!monthAvailability[selectedYear + '-' + (selectedMonth + 1)]) {
      return false;
    }
    // if is before today
    const today = new Date();
    if (selectedYear < today.getFullYear() || (selectedYear === today.getFullYear() && selectedMonth < today.getMonth()) || (selectedYear === today.getFullYear() && selectedMonth === today.getMonth() && day < today.getDate())) {
      return false;
    }
    return monthAvailability[selectedYear + '-' + (selectedMonth + 1)][day];
  }

  function timeslotsAreReady() {
    return timeSlots[selectedYear] && timeSlots[selectedYear][selectedMonth + 1] && timeSlots[selectedYear][selectedMonth + 1][selectedDay];
  }

  function isSelectedTimeSlot(time) {
    if (selectedTimeSlot) {
      const [year, month, day] = formatDate(selectedTimeSlot).split('-');
      const [hours, minutes] = time.split(':');
      return parseInt(day) === selectedDay && parseInt(month) === selectedMonth + 1 && parseInt(year) === selectedYear && selectedTimeSlot.getHours() === parseInt(hours) && selectedTimeSlot.getMinutes() === parseInt(minutes);
    }
    return false;
  }

  function Month(month) {
    return _vz('months').split(',')[month];
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

  

  async function confirmTimeSlot() {
    if (previewMode) return;
    const data = {
      calendar_id: calendarId,
      date_time: selectedTimeSlot,
      nonce: restNonce,
    };
    try {
      setConfirmationIsLoading(true);
      const response = await axios.post( restUrl + 'vz-am/v1/confirm_meeting', data, {
        headers: {
          'X-WP-Nonce': restNonce
        }
      });
      setPopup({
        open: true,
        message: 'Your meeting has been confirmed.',
        type: 'success'
      });
      setUserMeetings([...userMeetings, {
        id: response.data.id,
        date_time: selectedTimeSlot,
        duration: timeSlotSize
      }]);
      setSelectedTimeSlot(null);
      // remove slot from timeslots
      const newTimeSlots = { ...timeSlots };
      delete newTimeSlots[selectedYear][selectedMonth + 1][selectedDay][selectedTimeSlot.toLocaleTimeString('default', { hour: '2-digit', minute: '2-digit' })];
      setTimeSlots(newTimeSlots);

      setConfirmationIsLoading(false);
    } catch (error) {
      console.error(error);
      setConfirmationIsLoading(false);
      setPopup({
        open: true,
        message: 'There was an error fetching the time slots. Please try again later.',
        type: 'error'
      });
    }
  }

  const [highlightedDatetime, setHighlightedDatetimeVariable] = useState(null);
  function setHighlightedDateTime(date) {
    if (date) {
      setHighlightedDatetimeVariable(date);
    } else {
      setHighlightedDatetimeVariable(null);
    }
  }

  function dayIsHighlighted(day) {
    const month = selectedMonth + 1;
    const year = selectedYear;
    if (highlightedDatetime) {
      const [hYear, hMonth, hDay] = formatDate(highlightedDatetime).split('-');
      return parseInt(hDay) === day && parseInt(hMonth) === month && parseInt(hYear) === year;
    }
  }

  return (
    <section className={`vz-time-slot-selection ${preview ? '--vz-is-preview' : ''}`}>

      {(userMeetings.length > 0 && !previewMode) && (
        <div className="vz-meetings-list">
          <h2 className="vz-am__title">{_vz('your-meetings')}</h2>
          <ul>
            {userMeetings.map((meeting, index) => (
              <li key={index}>
                <article className="vz-am__user-meeting"
                          onMouseEnter={() => setHighlightedDateTime(meeting.date_time)}
                          onMouseLeave={() => setHighlightedDateTime(null)}
                          >
                  <h3>{getDateTimeInLocale(new Date(meeting.date_time), true)[0]}</h3>
                  <p className="week-time">
                    <span className="weekday">{getDayOfWeek(new Date(meeting.date_time))}</span>
                    <span className="time">{getDateTimeInLocale(new Date(meeting.date_time), true)[1]}</span>
                  </p>
                  <p className="duration">
                    {_vz('duration')}: {meeting.duration} {_vz('minutes')}
                  </p>
                </article>
              </li>
            ))}
          </ul>
        </div>
      )}
      <div className="vz-availability-calendar">
        <header>
          <button className="vz-calendar-nav__button --prev" onClick={(e) => previousMonth(e) }>
            {_vz('previous')}
          </button>
          <h1 className="vz-am__title">{Month(selectedMonth)} {selectedYear}</h1>
          <button className="vz-calendar-nav__button --next" onClick={(e) => nextMonth(e) }>
            {_vz('next')}
          </button>
        </header>
        <div className={
          `vz-calendar-grid ` +
          (monthIsLoading[selectedYear + '-' + (selectedMonth + 1)] ? '--loading' : '')
        }>
          {_vz('weekdays').split(',').map((day, index) => (
            <div className="day --header" key={index}>{day}</div>
          ))}
          {
            Array(getFirstDayOfMonth(selectedMonth, selectedYear)).fill(null).map((_, index) => (
              <div className="day --fill" key={index}></div>
            ))
          }
          {
            Array(getDaysInMonth(selectedMonth, selectedYear)).fill(null).map((_, index) => (
              <div className={`day --monthday ` + 
                ((index + 1 === selectedDay) ? ' --selected' : '') +
                (isToday(index + 1) ? ' --istoday' : '') +
                (dayIsHighlighted(index + 1) ? ' --highlighted' : '') +
                (isAvailable(index + 1) ? ' --available' : ' --unavailable')
              } key={index}>
                <button
                  onClick={() => setSelectedDay( index + 1)}
                  className="day-button"
                >
                  {index + 1}
                </button>
              </div>
            ))
          }
        </div>
      </div>
      <div className="vz-time-slots">
        <h2 className="vz-am__title">{selectedDateFormatted()}</h2>
        <p>
          {timeSlotSize} {_vz('minutes-per-slot')}
        </p>
        {
          timeSlotsAreLoading[selectedYear + '-' + (selectedMonth + 1) + '-' + selectedDay] && (
            <ul className="vz-am__loading-timeslots">
              <li></li>
              <li></li>
              <li></li>
              <li></li>
            </ul>
          )
        }
        <ul className="vz-am__time-slots__list">
          {
          timeslotsAreReady() && Object.keys(timeSlots[selectedYear][selectedMonth + 1][selectedDay]).map((time, index) => (
            <li className="vz-am__time-slots__item" key={index}>
              <button
                onClick={(e) => selectTimeSlot(e, time)}
                className={`vz-am__time-slot__button ` + (isSelectedTimeSlot(time) ? '--selected' : '')}
              >
                {time}
              </button>
            </li>
          ))
          }
        </ul>
        { timeslotsAreReady() && 
          !Object.keys(timeSlots[selectedYear][selectedMonth + 1][selectedDay]).length && (
              <p>
                {_vz('no-meetings')}
              </p>
        )}

        <p className="vz-am__visitor-timezone">
          <b> {_vz('your-timezone-is') } </b> {visitorTimeZone}
        </p>
        <p className="vz-am__website-timezone">
          <b> {_vz('website-timezone-is') } </b> {visitorTimeZone}
        </p>
      </div>
      {
        (
          (selectedTimeSlot || previewMode) &&
          <div className="vz-meeting-confirmation">
            <div className="vz-am__confirmation-box">
              <h2>
                {_vz('meeting-confirmation')}
              </h2>
              <p>
                {selectedTimeSlot ? `${_vz('you-selected')} ${getDateTimeInLocale(selectedTimeSlot)} ${_vz('for-meeting')}` : 'Please select a time slot for your meeting.'}
              </p>
              <button 
                disabled={confirmationIsLoading}
                className="vz-am__confirmation-button"
              onClick={() => confirmTimeSlot()}>
                {_vz('confirm')}
              </button>
            </div>
          </div>
        )
      }
      {
        popup.open && (
          <div className={`vz-popup --${popup.type}`}>
            <p>{popup.message}</p>
            <button onClick={() => setPopup({ ...popup, open: false })}>
              {_vz('Ok')}
            </button>
          </div>
        )
      }
    </section>
  );
}

export default App;
