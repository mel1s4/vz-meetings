import React, { useState, useEffect } from 'react';
import {_vz, Month, formatDate} from '../translations';
import './TimeSlots.scss';

export default function TimeSlots({
  timeSlots,
  selectedYear,
  selectedMonth,
  selectedDay,
  timeSlotSize,
  selectedTimeSlot,
  timeSlotsAreLoading,
  setSelectedTimeSlot,
  timeZone
}) {

  const [visitorTimeZone, setVisitorTimeZone] = useState(
    Intl.DateTimeFormat().resolvedOptions().timeZone
  );

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


  return (
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
      <b> {_vz('website-timezone-is') } </b> {timeZone}
    </p>
    
  </div>
  )
}