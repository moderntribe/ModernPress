import * as React from 'react';
const SvgIconNotificationBell = ( props ) => (
	<svg
		xmlns="http://www.w3.org/2000/svg"
		width="1em"
		height="1em"
		fill="none"
		viewBox="0 0 64 64"
		{ ...props }
	>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M26 59.444a8.32 8.32 0 0 0 12 0M52 30v-8.2A20.42 20.42 0 0 0 32 2a20.6 20.6 0 0 0-20 20v8c0 9-7.2 10.6-7.2 15.6 0 4.6 10.6 8.2 27.2 8.2s27.2-3.6 27.2-8.2c0-5-7.2-6.6-7.2-15.6Z"
		/>
	</svg>
);
export default SvgIconNotificationBell;
