import * as React from 'react';
const SvgIconRocketLaunch = ( props ) => (
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
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="m40.912 7.446 15.58 15.58M34.6 10.742C25.5 7.446 16 9.416 8 20l8.328 8.348M35.652 47.672 44 56c10.584-8 12.554-17.5 9.258-26.6"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M28 52 12 36S24.986 4.584 61.312 2.688C59.258 38.858 28 52 28 52Z"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M37 32a5 5 0 1 0 0-10 5 5 0 0 0 0 10ZM5.758 49.758a6 6 0 0 1 8.484 8.484C11.9 60.586 2.344 61.656 2.344 61.656s1.07-9.556 3.414-11.898Z"
		/>
	</svg>
);
export default SvgIconRocketLaunch;
