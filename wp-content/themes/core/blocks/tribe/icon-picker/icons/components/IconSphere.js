import * as React from 'react';
const SvgIconSphere = ( props ) => (
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
			d="M26 22.214c-1.72.12-3.4.278-5 .49M38 22.214c1.72.12 3.4.278 5 .49M54.388 25.36C59.108 27.126 62 29.448 62 32c0 5.522-13.432 10-30 10S2 37.522 2 32c0-2.552 2.892-4.874 7.612-6.64"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M32 62c16.569 0 30-13.431 30-30C62 15.432 48.569 2 32 2 15.432 2 2 15.432 2 32c0 16.569 13.432 30 30 30Z"
		/>
	</svg>
);
export default SvgIconSphere;
