import React, { useState, useEffect } from 'react';
import { _vz } from '../translations';


import './UserMeetings.scss';
export default function UserMeetings({
  userMeetings,
  setHighlightedDateTime,
  getDateTimeInLocale,
  getDayOfWeek,
}) {

  return (
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
  )
}