import * as React from 'react';
const SvgIconServiceBell = ( props ) => (
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
			d="M32 14V6M60 42a28 28 0 1 0-56 0v8h56zM4 58h56M42 6H22"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M12 42a20 20 0 0 1 20-20"
		/>
	</svg>
);
export default SvgIconServiceBell;
