import * as React from 'react';
const SvgIconEventCalendar = ( props ) => (
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
			d="M10 40h44M24 30v20M40 30v20M18 2v8M46 2v8M62 30v28H2V30M62 10H2v12h60z"
		/>
	</svg>
);
export default SvgIconEventCalendar;
