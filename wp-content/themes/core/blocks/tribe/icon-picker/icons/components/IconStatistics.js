import * as React from 'react';
const SvgIconStatistics = ( props ) => (
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
			d="M7.998 58 8 44M23.998 26v32M40.036 58 40 44M55.998 26v32M6 26 24 8l16 16L58 6"
		/>
	</svg>
);
export default SvgIconStatistics;
